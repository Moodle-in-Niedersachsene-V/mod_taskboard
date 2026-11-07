// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Task board: moving cards with pointer (mouse, touch, pen) and keyboard, card details, live updates.
 *
 * @module     mod_taskboard/board
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Templates from 'core/templates';
import {getString, getStrings} from 'core/str';

/** @type {number} Pointer movement in pixels before a press becomes a drag. */
const DRAG_THRESHOLD = 6;

/** @type {number} Distance from the viewport edge in pixels that scrolls the board while dragging. */
const SCROLL_EDGE = 48;

/** @type {number} Milliseconds the undo message stays visible. */
const TOAST_TIME = 7000;

/**
 * State and behaviour of one board on the page.
 */
class Board {
    /**
     * Sets up the board.
     *
     * @param {HTMLElement} root The page region.
     * @param {number} cmid Course module id.
     * @param {number} boardid Board id.
     * @param {number} timemodified Time of the rendered state.
     * @param {number} pollinterval Seconds between update checks, 0 for none.
     */
    constructor(root, cmid, boardid, timemodified, pollinterval) {
        this.root = root;
        this.cmid = cmid;
        this.boardid = boardid;
        this.timemodified = timemodified;
        this.pending = null;
        this.drag = null;
        this.keyboard = null;
        this.lastDragEnd = 0;
        this.toastTimer = null;
        this.undoMove = null;
        this.modal = null;
        this.busy = false;

        this.onPointerMove = this.onPointerMove.bind(this);
        this.onPointerUp = this.onPointerUp.bind(this);
        this.onPointerCancel = this.onPointerCancel.bind(this);

        root.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        root.addEventListener('click', (e) => this.onClick(e));
        root.addEventListener('keydown', (e) => this.onKeyDown(e));
        root.addEventListener('keyup', (e) => this.onKeyUp(e));
        root.addEventListener('dragstart', (e) => {
            if (e.target.closest('[data-region="mod_taskboard-card"]')) {
                e.preventDefault();
            }
        });

        if (pollinterval > 0) {
            window.setInterval(() => this.poll(), pollinterval * 1000);
        }
        this.linkKeyboardHint();
    }

    /**
     * Connects every card with the hidden keyboard instructions of the page.
     *
     * Done here rather than in the card template, because the template is also rendered on its own.
     */
    linkKeyboardHint() {
        const hintid = 'mod_taskboard-kbhint-' + this.cmid;
        this.root.querySelectorAll('[data-region="mod_taskboard-card"]').forEach((card) => {
            card.setAttribute('aria-describedby', hintid);
        });
    }

    /**
     * Returns the region holding the columns.
     *
     * @returns {HTMLElement}
     */
    get columnsRegion() {
        return this.root.querySelector('[data-region="mod_taskboard-columns"]');
    }

    /**
     * Calls one external function of the plugin.
     *
     * @param {string} name Function name without the mod_taskboard_ prefix.
     * @param {object} args Arguments without cmid.
     * @returns {Promise}
     */
    call(name, args) {
        return Ajax.call([{methodname: 'mod_taskboard_' + name, args: Object.assign({cmid: this.cmid}, args)}])[0];
    }

    /**
     * Announces a message to screen reader users.
     *
     * @param {string} message The message.
     */
    announce(message) {
        const live = this.root.querySelector('[data-region="mod_taskboard-live"]');
        live.textContent = '';
        window.setTimeout(() => {
            live.textContent = message;
        }, 50);
    }

    /**
     * Fetches the board and renders it again if it changed.
     *
     * @param {boolean} force Render even if the server reports no change.
     * @returns {Promise}
     */
    refresh(force) {
        const pending = new Pending('mod_taskboard/refresh');
        return this.call('get_board', {boardid: this.boardid, since: force ? 0 : this.timemodified})
            .then((result) => {
                if (!result.changed || !result.board.length || this.drag || this.keyboard) {
                    return null;
                }
                return this.render(result.board[0]);
            })
            .then(() => pending.resolve())
            .catch((error) => {
                pending.resolve();
                return Notification.exception(error);
            });
    }

    /**
     * Renders the columns from board data.
     *
     * @param {object} board Board data as returned by get_board.
     * @returns {Promise}
     */
    render(board) {
        const focused = document.activeElement ? document.activeElement.closest('[data-region="mod_taskboard-card"]') : null;
        const focusId = focused ? focused.dataset.cardid : null;
        const context = Object.assign({cmid: this.cmid}, board);
        return Templates.renderForPromise('mod_taskboard/columns', context)
            .then(({html, js}) => {
                Templates.replaceNodeContents(this.columnsRegion, html, js);
                this.linkKeyboardHint();
                this.timemodified = board.timemodified;
                if (focusId) {
                    const card = this.findCard(focusId);
                    if (card) {
                        card.focus();
                    }
                }
                return null;
            });
    }

    /**
     * Checks for changes made by others, unless the user is busy with a card.
     */
    poll() {
        if (document.hidden || this.drag || this.pending || this.keyboard || this.busy) {
            return;
        }
        this.refresh(false);
    }

    /**
     * Returns the card element with the given id.
     *
     * @param {string|number} cardid Card id.
     * @returns {HTMLElement|null}
     */
    findCard(cardid) {
        return this.root.querySelector('[data-region="mod_taskboard-card"][data-cardid="' + parseInt(cardid, 10) + '"]');
    }

    /**
     * Returns the position of a card in its list, not counting the card being moved.
     *
     * @param {HTMLElement} element The card or placeholder.
     * @param {HTMLElement|null} ignore A card to leave out of the count.
     * @returns {number}
     */
    positionOf(element, ignore) {
        let position = 0;
        for (const child of element.parentNode.children) {
            if (child === element) {
                break;
            }
            if (child !== ignore && child.matches('[data-region="mod_taskboard-card"]')) {
                position++;
            }
        }
        return position;
    }

    /**
     * Updates the card counts and limit warnings of all columns from the current DOM.
     */
    updateCounts() {
        this.root.querySelectorAll('[data-region="mod_taskboard-column"]').forEach((column) => {
            const list = column.querySelector('[data-region="mod_taskboard-cardlist"]');
            let count = 0;
            for (const child of list.children) {
                const isCard = child.matches('[data-region="mod_taskboard-card"]');
                const visible = isCard && !child.classList.contains('mod_taskboard-hidden');
                if (visible || child.classList.contains('mod_taskboard-placeholder')) {
                    count++;
                }
            }
            const limit = parseInt(column.dataset.wiplimit, 10);
            const badge = column.querySelector('[data-region="mod_taskboard-count"]');
            badge.textContent = limit > 0 ? count + ' / ' + limit : String(count);
            column.classList.toggle('mod_taskboard-overlimit', limit > 0 && count > limit);
        });
    }

    /**
     * Starts watching a press on a card that may become a drag.
     *
     * @param {PointerEvent} e The event.
     */
    onPointerDown(e) {
        const card = e.target.closest('[data-region="mod_taskboard-card"]');
        if (!card || card.dataset.canmove !== '1' || this.keyboard || this.drag) {
            return;
        }
        if (e.pointerType === 'mouse' && e.button !== 0) {
            return;
        }
        const rect = card.getBoundingClientRect();
        this.pending = {
            card: card,
            pointerId: e.pointerId,
            startX: e.clientX,
            startY: e.clientY,
            offsetX: e.clientX - rect.left,
            offsetY: e.clientY - rect.top,
            width: rect.width,
            height: rect.height,
        };
        window.addEventListener('pointermove', this.onPointerMove, {passive: false});
        window.addEventListener('pointerup', this.onPointerUp);
        window.addEventListener('pointercancel', this.onPointerCancel);
    }

    /**
     * Turns a press into a drag once it moved far enough, then follows the pointer.
     *
     * @param {PointerEvent} e The event.
     */
    onPointerMove(e) {
        const pending = this.pending;
        if (!pending || e.pointerId !== pending.pointerId) {
            return;
        }
        if (!this.drag) {
            if (Math.abs(e.clientX - pending.startX) + Math.abs(e.clientY - pending.startY) < DRAG_THRESHOLD) {
                return;
            }
            this.startDrag();
        }
        if (e.cancelable) {
            e.preventDefault();
        }
        const drag = this.drag;
        drag.ghost.style.transform = 'translate(' + (e.clientX - pending.offsetX) + 'px, '
            + (e.clientY - pending.offsetY) + 'px) rotate(2deg)';
        this.autoScroll(e.clientX, e.clientY);
        this.placePlaceholder(e.clientX, e.clientY);
    }

    /**
     * Creates the floating copy of the card and the placeholder that shows where it will land.
     */
    startDrag() {
        const pending = this.pending;
        const card = pending.card;
        const placeholder = document.createElement('div');
        placeholder.className = 'mod_taskboard-placeholder';
        placeholder.style.height = pending.height + 'px';
        placeholder.setAttribute('aria-hidden', 'true');
        card.parentNode.insertBefore(placeholder, card);

        const ghost = card.cloneNode(true);
        ghost.classList.add('mod_taskboard-ghost');
        ghost.removeAttribute('data-region');
        ghost.setAttribute('aria-hidden', 'true');
        ghost.style.width = pending.width + 'px';
        this.root.appendChild(ghost);

        card.classList.add('mod_taskboard-hidden');
        this.root.classList.add('mod_taskboard-dragging');
        this.drag = {
            card: card,
            ghost: ghost,
            placeholder: placeholder,
            fromColumn: parseInt(card.closest('[data-region="mod_taskboard-cardlist"]').dataset.columnid, 10),
            fromPosition: this.positionOf(card, null),
        };
        this.updateCounts();
    }

    /**
     * Moves the placeholder to the list and position under the pointer.
     *
     * @param {number} x Pointer x in the viewport.
     * @param {number} y Pointer y in the viewport.
     */
    placePlaceholder(x, y) {
        const drag = this.drag;
        const element = document.elementFromPoint(x, y);
        const column = element ? element.closest('[data-region="mod_taskboard-column"]') : null;
        if (!column || !this.root.contains(column)) {
            return;
        }
        const list = column.querySelector('[data-region="mod_taskboard-cardlist"]');
        let before = null;
        for (const child of list.children) {
            if (child === drag.card || child === drag.placeholder || !child.matches('[data-region="mod_taskboard-card"]')) {
                continue;
            }
            const rect = child.getBoundingClientRect();
            if (y < rect.top + rect.height / 2) {
                before = child;
                break;
            }
        }
        if (drag.placeholder.parentNode !== list || drag.placeholder.nextElementSibling !== before) {
            list.insertBefore(drag.placeholder, before);
            this.updateCounts();
        }
    }

    /**
     * Scrolls the board and the page when the pointer is near an edge.
     *
     * @param {number} x Pointer x in the viewport.
     * @param {number} y Pointer y in the viewport.
     */
    autoScroll(x, y) {
        const scroller = this.root.querySelector('[data-region="mod_taskboard-scroll"]');
        if (x < SCROLL_EDGE) {
            scroller.scrollLeft -= 14;
        } else if (x > window.innerWidth - SCROLL_EDGE) {
            scroller.scrollLeft += 14;
        }
        if (y < SCROLL_EDGE) {
            window.scrollBy(0, -14);
        } else if (y > window.innerHeight - SCROLL_EDGE) {
            window.scrollBy(0, 14);
        }
    }

    /**
     * Ends a press: drops a dragged card, or lets the click open the card.
     */
    onPointerUp() {
        const drag = this.drag;
        this.stopWatchingPointer();
        if (!drag) {
            return;
        }
        this.lastDragEnd = Date.now();
        const list = drag.placeholder.parentNode;
        const columnid = parseInt(list.dataset.columnid, 10);
        const position = this.positionOf(drag.placeholder, drag.card);
        list.insertBefore(drag.card, drag.placeholder);
        this.endDrag();
        drag.card.focus();
        if (columnid !== drag.fromColumn || position !== drag.fromPosition) {
            this.saveMove(drag.card, columnid, position, drag.fromColumn, drag.fromPosition);
        }
    }

    /**
     * Cancels a drag, for example when the browser takes over the touch.
     */
    onPointerCancel() {
        const drag = this.drag;
        this.stopWatchingPointer();
        if (!drag) {
            return;
        }
        drag.placeholder.parentNode.insertBefore(drag.card, drag.placeholder);
        this.endDrag();
        this.refresh(true);
    }

    /**
     * Removes the window listeners of a press.
     */
    stopWatchingPointer() {
        this.pending = null;
        window.removeEventListener('pointermove', this.onPointerMove);
        window.removeEventListener('pointerup', this.onPointerUp);
        window.removeEventListener('pointercancel', this.onPointerCancel);
    }

    /**
     * Removes ghost and placeholder and shows the card again.
     */
    endDrag() {
        const drag = this.drag;
        drag.ghost.remove();
        drag.placeholder.remove();
        drag.card.classList.remove('mod_taskboard-hidden');
        this.root.classList.remove('mod_taskboard-dragging');
        this.drag = null;
        this.updateCounts();
    }

    /**
     * Stores a move on the server and offers to undo it.
     *
     * @param {HTMLElement} card The moved card.
     * @param {number} columnid Target column id.
     * @param {number} position Target position.
     * @param {number} fromColumn Original column id.
     * @param {number} fromPosition Original position.
     * @returns {Promise}
     */
    saveMove(card, columnid, position, fromColumn, fromPosition) {
        const pending = new Pending('mod_taskboard/move');
        const cardid = parseInt(card.dataset.cardid, 10);
        const column = this.root.querySelector('[data-region="mod_taskboard-column"][data-columnid="' + columnid + '"]');
        const columnTitle = column ? column.querySelector('h4').textContent.trim() : '';
        return this.call('move_card', {cardid: cardid, columnid: columnid, position: position})
            .then(() => getString('movedto', 'mod_taskboard', {column: columnTitle, position: position + 1}))
            .then((message) => {
                this.announce(message);
                this.showUndo(message, {cardid: cardid, columnid: fromColumn, position: fromPosition});
                return this.refresh(true);
            })
            .then(() => pending.resolve())
            .catch((error) => {
                pending.resolve();
                this.refresh(true);
                return Notification.exception(error);
            });
    }

    /**
     * Shows the undo message for the last move.
     *
     * @param {string} message The text to show.
     * @param {object} undo The move that restores the previous state.
     */
    showUndo(message, undo) {
        const toast = this.root.querySelector('[data-region="mod_taskboard-toast"]');
        toast.querySelector('[data-region="mod_taskboard-toast-text"]').textContent = message;
        toast.classList.remove('d-none');
        this.undoMove = undo;
        window.clearTimeout(this.toastTimer);
        this.toastTimer = window.setTimeout(() => this.hideUndo(), TOAST_TIME);
    }

    /**
     * Hides the undo message.
     */
    hideUndo() {
        this.root.querySelector('[data-region="mod_taskboard-toast"]').classList.add('d-none');
        this.undoMove = null;
    }

    /**
     * Reverts the last move.
     *
     * @returns {Promise|null}
     */
    undo() {
        const undo = this.undoMove;
        this.hideUndo();
        if (!undo) {
            return null;
        }
        return this.call('move_card', undo)
            .then(() => getString('moveundone', 'mod_taskboard'))
            .then((message) => {
                this.announce(message);
                return this.refresh(true);
            })
            .catch(Notification.exception);
    }

    /**
     * Handles keys on cards: Space picks up and drops, arrows move, Escape cancels.
     *
     * @param {KeyboardEvent} e The event.
     */
    onKeyDown(e) {
        const card = e.target.closest('[data-region="mod_taskboard-card"]');
        if (!card) {
            return;
        }
        const keyboard = this.keyboard;
        if (keyboard && keyboard.card === card) {
            if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
                e.preventDefault();
                this.keyboardMove(e.key);
            } else if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                this.keyboardDrop();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.keyboardCancel();
            }
            return;
        }
        if (e.key === ' ' && !keyboard && card.dataset.canmove === '1') {
            e.preventDefault();
            this.keyboardPick(card);
        }
    }

    /**
     * Stops the browser from clicking the card when Space is released after picking it up.
     *
     * @param {KeyboardEvent} e The event.
     */
    onKeyUp(e) {
        if (e.key === ' ' && e.target.closest('[data-region="mod_taskboard-card"]')) {
            e.preventDefault();
        }
    }

    /**
     * Picks up a card for moving with the keyboard.
     *
     * @param {HTMLElement} card The card.
     */
    keyboardPick(card) {
        const list = card.parentNode;
        this.keyboard = {
            card: card,
            fromColumn: parseInt(list.dataset.columnid, 10),
            fromPosition: this.positionOf(card, null),
            originalNext: card.nextElementSibling,
            originalList: list,
        };
        card.classList.add('mod_taskboard-grabbed');
        card.setAttribute('aria-pressed', 'true');
        getString('pickedup', 'mod_taskboard', card.querySelector('.mod_taskboard-title').textContent)
            .then((message) => this.announce(message))
            .catch(Notification.exception);
    }

    /**
     * Moves the picked-up card one step.
     *
     * @param {string} key The arrow key.
     */
    keyboardMove(key) {
        const card = this.keyboard.card;
        const list = card.parentNode;
        const lists = Array.from(this.root.querySelectorAll('[data-region="mod_taskboard-cardlist"]'));
        const index = lists.indexOf(list);
        const cards = (element) => Array.from(element.children).filter((c) => c.matches('[data-region="mod_taskboard-card"]'));
        if (key === 'ArrowUp') {
            const previous = card.previousElementSibling;
            if (previous) {
                list.insertBefore(card, previous);
            }
        } else if (key === 'ArrowDown') {
            const next = card.nextElementSibling;
            if (next) {
                list.insertBefore(next, card);
            }
        } else {
            const target = lists[index + (key === 'ArrowLeft' ? -1 : 1)];
            if (!target) {
                return;
            }
            const position = Math.min(this.positionOf(card, null), cards(target).length);
            target.insertBefore(card, cards(target)[position] || null);
        }
        card.focus();
        this.updateCounts();
        const column = card.closest('[data-region="mod_taskboard-column"]');
        getString('keyboardposition', 'mod_taskboard', {
            column: column.querySelector('h4').textContent.trim(),
            position: this.positionOf(card, null) + 1,
            total: cards(card.parentNode).length,
        })
            .then((message) => this.announce(message))
            .catch(Notification.exception);
    }

    /**
     * Drops the picked-up card where it is and stores the move.
     */
    keyboardDrop() {
        const keyboard = this.keyboard;
        const card = keyboard.card;
        this.keyboard = null;
        card.classList.remove('mod_taskboard-grabbed');
        card.setAttribute('aria-pressed', 'false');
        const columnid = parseInt(card.parentNode.dataset.columnid, 10);
        const position = this.positionOf(card, null);
        if (columnid !== keyboard.fromColumn || position !== keyboard.fromPosition) {
            this.saveMove(card, columnid, position, keyboard.fromColumn, keyboard.fromPosition);
        }
    }

    /**
     * Puts the picked-up card back where it was.
     */
    keyboardCancel() {
        const keyboard = this.keyboard;
        const card = keyboard.card;
        this.keyboard = null;
        keyboard.originalList.insertBefore(card, keyboard.originalNext);
        card.classList.remove('mod_taskboard-grabbed');
        card.setAttribute('aria-pressed', 'false');
        card.focus();
        this.updateCounts();
        getString('movecancelled', 'mod_taskboard')
            .then((message) => this.announce(message))
            .catch(Notification.exception);
    }

    /**
     * Dispatches clicks on cards and action buttons.
     *
     * @param {MouseEvent} e The event.
     */
    onClick(e) {
        const action = e.target.closest('[data-action]');
        if (action && this.root.contains(action)) {
            this.handleAction(action, e);
            return;
        }
        const card = e.target.closest('[data-region="mod_taskboard-card"]');
        if (card && !this.keyboard && Date.now() - this.lastDragEnd > 400) {
            this.openCard(parseInt(card.dataset.cardid, 10));
        }
    }

    /**
     * Runs the action of a button on the board.
     *
     * @param {HTMLElement} button The button.
     * @param {Event} e The click event.
     */
    handleAction(button, e) {
        const columnid = parseInt(button.dataset.columnid || '0', 10);
        switch (button.dataset.action) {
            case 'cardsize':
                e.preventDefault();
                this.setCardSize(button.dataset.size);
                break;
            case 'addcard':
                this.openCardForm(0, columnid);
                break;
            case 'addcolumn':
                this.openColumnForm(0);
                break;
            case 'editcolumn':
                this.openColumnForm(columnid);
                break;
            case 'movecolumn':
                this.call('move_column', {columnid: columnid, direction: parseInt(button.dataset.direction, 10)})
                    .then(() => this.refresh(true))
                    .catch(Notification.exception);
                break;
            case 'deletecolumn':
                this.deleteColumn(columnid);
                break;
            case 'undo':
                this.undo();
                break;
            default:
                break;
        }
    }

    /**
     * Switches the card size and stores it as the user's preference.
     *
     * @param {string} size One of auto, s, m, l.
     */
    setCardSize(size) {
        ['auto', 's', 'm', 'l'].forEach((key) => this.root.classList.remove('mod_taskboard-size-' + key));
        this.root.classList.add('mod_taskboard-size-' + size);
        this.root.querySelectorAll('[data-action="cardsize"]').forEach((button) => {
            const active = button.dataset.size === size;
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        this.call('set_cardsize', {size: size}).catch(Notification.exception);
    }

    /**
     * Opens the form to add or edit a card.
     *
     * @param {number} cardid Card id, 0 for a new card.
     * @param {number} columnid Column of a new card.
     */
    openCardForm(cardid, columnid) {
        this.busy = true;
        const form = new ModalForm({
            formClass: 'mod_taskboard\\form\\card_form',
            args: {cmid: this.cmid, boardid: this.boardid, cardid: cardid, columnid: columnid},
            modalConfig: {title: getString(cardid ? 'editcard' : 'addcard', 'mod_taskboard'), large: true},
        });
        form.addEventListener(form.events.FORM_SUBMITTED, () => {
            this.busy = false;
            this.refresh(true);
            if (this.modal) {
                this.modal.destroy();
                this.modal = null;
            }
        });
        form.addEventListener(form.events.CANCEL_BUTTON_PRESSED, () => {
            this.busy = false;
        });
        form.show();
    }

    /**
     * Opens the form to add or edit a column.
     *
     * @param {number} columnid Column id, 0 for a new column.
     */
    openColumnForm(columnid) {
        const form = new ModalForm({
            formClass: 'mod_taskboard\\form\\column_form',
            args: {cmid: this.cmid, boardid: this.boardid, columnid: columnid},
            modalConfig: {title: getString(columnid ? 'editcolumn' : 'addcolumn', 'mod_taskboard')},
        });
        form.addEventListener(form.events.FORM_SUBMITTED, () => this.refresh(true));
        form.show();
    }

    /**
     * Asks for confirmation and deletes a column with its cards.
     *
     * @param {number} columnid Column id.
     * @returns {Promise}
     */
    deleteColumn(columnid) {
        return getStrings([
            {key: 'deletecolumn', component: 'mod_taskboard'},
            {key: 'deletecolumnconfirm', component: 'mod_taskboard'},
            {key: 'delete', component: 'core'},
        ])
            .then(([title, question, label]) => Notification.deleteCancelPromise(title, question, label))
            .then(() => this.call('delete_column', {columnid: columnid}))
            .then(() => this.refresh(true))
            .catch((error) => {
                if (error && error.message) {
                    return Notification.exception(error);
                }
                return null;
            });
    }

    /**
     * Shows a card in full in a modal.
     *
     * @param {number} cardid Card id.
     * @returns {Promise}
     */
    openCard(cardid) {
        const pending = new Pending('mod_taskboard/opencard');
        this.busy = true;
        return this.call('get_card', {cardid: cardid})
            .then((card) => Modal.create({
                title: card.title,
                body: Templates.render('mod_taskboard/card_detail', card),
                large: true,
                show: true,
                removeOnClose: true,
            }))
            .then((modal) => {
                this.modal = modal;
                this.bindDetail(modal, cardid);
                pending.resolve();
                return modal;
            })
            .catch((error) => {
                this.busy = false;
                pending.resolve();
                return Notification.exception(error);
            });
    }

    /**
     * Connects the buttons, checkboxes and paste handling of an open card.
     *
     * @param {Modal} modal The modal.
     * @param {number} cardid Card id.
     */
    bindDetail(modal, cardid) {
        const root = modal.getRoot()[0];
        const onPaste = (e) => this.onPaste(e);
        document.addEventListener('paste', onPaste);
        modal.getRoot().on(ModalEvents.hidden, () => {
            document.removeEventListener('paste', onPaste);
            this.busy = false;
            this.modal = null;
            const card = this.findCard(cardid);
            if (card) {
                card.focus();
            }
        });
        root.addEventListener('change', (e) => {
            const checkbox = e.target.closest('[data-action="togglecheck"]');
            if (!checkbox) {
                return;
            }
            this.call('set_check', {cardid: cardid, checkid: parseInt(checkbox.dataset.checkid, 10), done: checkbox.checked})
                .then(() => {
                    this.updateCheckProgress(root);
                    return this.refresh(true);
                })
                .catch(Notification.exception);
        });
        root.addEventListener('click', (e) => {
            const button = e.target.closest('[data-action]');
            if (!button) {
                return;
            }
            if (button.dataset.action === 'editcard') {
                this.openCardForm(cardid, 0);
            } else if (button.dataset.action === 'approvecard') {
                this.call('approve_card', {cardid: cardid})
                    .then(() => {
                        modal.destroy();
                        return this.refresh(true);
                    })
                    .catch(Notification.exception);
            } else if (button.dataset.action === 'deletecard') {
                this.deleteCard(cardid, modal);
            } else if (button.dataset.action === 'savepasted') {
                this.savePasted(root, cardid);
            }
        });
    }

    /**
     * Updates the checklist progress shown in an open card.
     *
     * @param {HTMLElement} root The modal root.
     */
    updateCheckProgress(root) {
        const boxes = root.querySelectorAll('[data-action="togglecheck"]');
        const done = Array.from(boxes).filter((box) => box.checked).length;
        const count = root.querySelector('[data-region="mod_taskboard-checkcount"]');
        const bar = root.querySelector('[data-region="mod_taskboard-checkbar"]');
        if (count) {
            count.textContent = done + '/' + boxes.length;
        }
        if (bar) {
            bar.style.width = (boxes.length ? Math.round(done / boxes.length * 100) : 0) + '%';
        }
    }

    /**
     * Asks for confirmation and deletes a card.
     *
     * @param {number} cardid Card id.
     * @param {Modal} modal The open card modal.
     * @returns {Promise}
     */
    deleteCard(cardid, modal) {
        return getStrings([
            {key: 'deletecard', component: 'mod_taskboard'},
            {key: 'deletecardconfirm', component: 'mod_taskboard'},
            {key: 'delete', component: 'core'},
        ])
            .then(([title, question, label]) => Notification.deleteCancelPromise(title, question, label))
            .then(() => this.call('delete_card', {cardid: cardid}))
            .then(() => {
                modal.destroy();
                return this.refresh(true);
            })
            .catch((error) => {
                if (error && error.message) {
                    return Notification.exception(error);
                }
                return null;
            });
    }

    /**
     * Shows an image pasted from the clipboard in the open card, ready to be saved with a description.
     *
     * @param {ClipboardEvent} e The event.
     */
    onPaste(e) {
        const root = this.modal ? this.modal.getRoot()[0] : null;
        const panel = root ? root.querySelector('[data-region="mod_taskboard-pastepanel"]') : null;
        if (!panel || !e.clipboardData) {
            return;
        }
        const item = Array.from(e.clipboardData.items).find((entry) => entry.kind === 'file' && entry.type.startsWith('image/'));
        if (!item) {
            return;
        }
        e.preventDefault();
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            panel.querySelector('[data-region="mod_taskboard-pastepreview"]').src = reader.result;
            panel.classList.remove('d-none');
            panel.querySelector('[data-region="mod_taskboard-pastealt"]').focus();
        });
        reader.readAsDataURL(item.getAsFile());
    }

    /**
     * Stores the pasted image of the open card.
     *
     * @param {HTMLElement} root The modal root.
     * @param {number} cardid Card id.
     * @returns {Promise}
     */
    savePasted(root, cardid) {
        const preview = root.querySelector('[data-region="mod_taskboard-pastepreview"]');
        const alt = root.querySelector('[data-region="mod_taskboard-pastealt"]').value;
        return this.call('set_card_image', {cardid: cardid, imagedata: preview.src, imagealt: alt})
            .then(() => {
                this.modal.destroy();
                return this.refresh(true);
            })
            .then(() => this.openCard(cardid))
            .catch(Notification.exception);
    }
}

/**
 * Starts the board on the page.
 *
 * @param {number} cmid Course module id.
 * @param {number} boardid Board id.
 * @param {number} timemodified Time of the rendered state.
 * @param {number} pollinterval Seconds between update checks, 0 for none.
 */
export const init = (cmid, boardid, timemodified, pollinterval) => {
    const root = document.querySelector('[data-region="mod_taskboard-page"][data-cmid="' + cmid + '"]');
    if (!root) {
        return null;
    }
    return new Board(root, cmid, boardid, timemodified, pollinterval);
};
