const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

// A small DOM fixture exercises the shipped event handlers without dependencies.
function setup() {
    const listeners = {};
    const parent = {};
    const items = [];
    const status = {};
    let hit = null;
    function makeItem(name, owner = parent) {
        const classes = new Set();
        const classList = { add: value => classes.add(value), remove: value => classes.delete(value) };
        const up = {}, down = {};
        const item = {
            dataset: { name }, parentElement: owner, classList,
            get previousElementSibling() { return items[items.indexOf(item) - 1]; },
            get nextElementSibling() { return items[items.indexOf(item) + 1]; },
            before(moving) { items.splice(items.indexOf(moving), 1); items.splice(items.indexOf(item), 0, moving); },
            after(moving) { items.splice(items.indexOf(moving), 1); items.splice(items.indexOf(item) + 1, 0, moving); },
            querySelector(selector) { return selector === '.team-order-row' ? row : handle; },
        };
        const row = {
            parentElement: item, classList,
            closest(selector) { return selector === '.team-order-row' ? row : null; },
            getBoundingClientRect() { return { top: 100, height: 40 }; },
            querySelector(selector) { return selector.includes('up') ? up : down; },
        };
        let captured = null;
        const handle = {
            closest(selector) { return selector === '.team-drag-handle' ? handle : item; },
            matches(selector) { return selector === '.team-drag-handle'; },
            focus() {},
            setPointerCapture(id) { captured = id; },
            hasPointerCapture(id) { return captured === id; },
            releasePointerCapture() { captured = null; },
        };
        item.row = row;
        item.handle = handle;
        items.push(item);
        return item;
    }
    const a = makeItem('A'), b = makeItem('B'), c = makeItem('C');
    const tree = {
        querySelectorAll(selector) { return selector === '.team-order-item' ? items : items.map(item => item.row); },
        addEventListener(type, callback) { listeners[type] = callback; },
    };
    const document = {
        getElementById(id) { return id === 'team-order-tree' ? tree : status; },
        elementFromPoint() { return hit; },
    };
    vm.runInNewContext(readFileSync('webroot/js/team-order.js', 'utf8'), { document });
    const send = (type, target, overrides = {}) => listeners[type]({
        target, pointerId: 1, pointerType: 'mouse', button: 0, isPrimary: true,
        clientX: 10, clientY: 135, preventDefault() {}, ...overrides,
    });
    return { a, b, c, items, status, send, hit: item => { hit = item?.row; } };
}

for (const pointerType of ['mouse', 'touch', 'pen']) {
    test(`${pointerType} can drag down and up while events are captured by the handle`, () => {
        const ui = setup();
        ui.send('pointerdown', ui.a.handle, { pointerType });
        ui.hit(ui.c);
        ui.send('pointermove', ui.a.handle, { pointerType });
        ui.send('pointerup', ui.a.handle, { pointerType });
        assert.deepEqual(ui.items.map(item => item.dataset.name), ['B', 'C', 'A']);
        assert.match(ui.status.textContent, /A moved/);
        ui.send('pointerdown', ui.a.handle, { pointerType });
        ui.hit(ui.b);
        ui.send('pointerup', ui.a.handle, { pointerType, clientY: 105 });
        assert.deepEqual(ui.items.map(item => item.dataset.name), ['A', 'B', 'C']);
    });
}

test('cancelled drags and drops outside the tree leave order unchanged', () => {
    const ui = setup();
    ui.send('pointerdown', ui.a.handle);
    ui.hit(ui.c);
    ui.send('pointermove', ui.a.handle);
    ui.send('pointercancel', ui.a.handle);
    assert.deepEqual(ui.items.map(item => item.dataset.name), ['A', 'B', 'C']);
    ui.send('pointerdown', ui.a.handle);
    ui.send('pointermove', ui.a.handle);
    ui.hit(null);
    ui.send('pointerup', ui.a.handle);
    assert.deepEqual(ui.items.map(item => item.dataset.name), ['A', 'B', 'C']);
});

test('a team cannot be dropped into another parent', () => {
    const ui = setup();
    ui.c.parentElement = {};
    ui.send('pointerdown', ui.a.handle);
    ui.hit(ui.c);
    ui.send('pointermove', ui.a.handle);
    ui.send('pointerup', ui.a.handle);
    assert.deepEqual(ui.items.map(item => item.dataset.name), ['A', 'B', 'C']);
});

test('keyboard arrows still move the focused team', () => {
    const ui = setup();
    ui.send('keydown', ui.a.handle, { key: 'ArrowDown' });
    assert.deepEqual(ui.items.map(item => item.dataset.name), ['B', 'A', 'C']);
    ui.send('keydown', ui.a.handle, { key: 'ArrowUp' });
    assert.deepEqual(ui.items.map(item => item.dataset.name), ['A', 'B', 'C']);
});
