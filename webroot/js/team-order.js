(() => {
    const tree = document.getElementById('team-order-tree');
    if (!tree) return;
    const status = document.getElementById('team-order-status');
    let dragged = null;
    let destination = null;
    let after = false;
    let activePointer = null;
    let handle = null;

    function refresh() {
        tree.querySelectorAll('.team-order-item').forEach(item => {
            const row = item.querySelector('.team-order-row');
            row.querySelector('[data-direction="up"]').disabled = !item.previousElementSibling;
            row.querySelector('[data-direction="down"]').disabled = !item.nextElementSibling;
        });
    }

    function changed(item) {
        refresh();
        status.textContent = `${item.dataset.name} moved. Select Save order to keep your changes.`;
    }

    function move(item, direction) {
        const sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (direction === 'up') sibling.before(item);
        else sibling.after(item);
        item.querySelector('.team-drag-handle').focus();
        changed(item);
    }

    function clearTarget() {
        tree.querySelectorAll('.drop-before, .drop-after').forEach(row => {
            row.classList.remove('drop-before', 'drop-after');
        });
        destination = null;
    }

    function targetAt(target, clientY) {
        clearTarget();
        const row = target?.closest('.team-order-row');
        const item = row?.parentElement;
        if (!dragged || !item || item === dragged || item.parentElement !== dragged.parentElement) return;
        destination = item;
        const bounds = row.getBoundingClientRect();
        after = clientY > bounds.top + bounds.height / 2;
        row.classList.add(after ? 'drop-after' : 'drop-before');
    }

    function finish() {
        if (dragged && destination) {
            if (after) destination.after(dragged);
            else destination.before(dragged);
            changed(dragged);
        }
        dragged?.classList.remove('is-dragging');
        dragged = null;
        clearTarget();
    }

    function collapse(button, collapsed) {
        const children = document.getElementById(button.getAttribute('aria-controls'));
        children.hidden = collapsed;
        button.setAttribute('aria-expanded', String(!collapsed));
        const name = button.closest('.team-order-item').dataset.name;
        button.setAttribute('aria-label', `${collapsed ? 'Expand' : 'Collapse'} ${name}`);
        button.textContent = collapsed ? '▸' : '▾';
    }

    tree.addEventListener('click', event => {
        const toggle = event.target.closest('.team-collapse');
        if (toggle) {
            collapse(toggle, toggle.getAttribute('aria-expanded') === 'true');
            return;
        }
        const all = event.target.closest('[data-collapse-all]');
        if (all) {
            tree.querySelectorAll('.team-collapse').forEach(button => {
                collapse(button, all.dataset.collapseAll === 'true');
            });
            return;
        }
        const button = event.target.closest('.team-move');
        if (button) move(button.closest('.team-order-item'), button.dataset.direction);
    });
    tree.addEventListener('keydown', event => {
        if (!event.target.matches('.team-drag-handle')) return;
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            move(event.target.closest('.team-order-item'), event.key === 'ArrowUp' ? 'up' : 'down');
        }
    });
    // Use the same pointer interaction for mouse, touch and pen. Native dragging
    // on a button is inconsistent across browsers and can cancel pointer events.
    tree.addEventListener('pointerdown', event => {
        const target = event.target.closest('.team-drag-handle');
        if (!target || activePointer !== null || event.button !== 0 || event.isPrimary === false) return;
        activePointer = event.pointerId;
        handle = target;
        dragged = target.closest('.team-order-item');
        dragged.classList.add('is-dragging');
        target.focus();
        target.setPointerCapture(activePointer);
        event.preventDefault();
    });
    tree.addEventListener('pointermove', event => {
        if (event.pointerId !== activePointer) return;
        targetAt(document.elementFromPoint(event.clientX, event.clientY), event.clientY);
        event.preventDefault();
    });

    function endPointer(event, cancelled = false) {
        if (event.pointerId !== activePointer) return;
        if (cancelled) clearTarget();
        else targetAt(document.elementFromPoint(event.clientX, event.clientY), event.clientY);
        const capturedHandle = handle;
        const pointerId = activePointer;
        activePointer = null;
        handle = null;
        finish();
        if (capturedHandle.hasPointerCapture(pointerId)) capturedHandle.releasePointerCapture(pointerId);
    }

    tree.addEventListener('pointerup', event => endPointer(event));
    tree.addEventListener('pointercancel', event => endPointer(event, true));
    tree.addEventListener('lostpointercapture', event => endPointer(event, true));
    tree.addEventListener('dragstart', event => event.preventDefault());
    refresh();
})();
