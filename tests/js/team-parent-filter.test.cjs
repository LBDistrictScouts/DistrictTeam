const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

function setup(selected = '', groupId = '', sectionId = '') {
    const control = value => ({ value, listeners: {}, addEventListener(event, fn) { this.listeners[event] = fn; } });
    const group = control(groupId), section = control(sectionId), parent = control(selected);
    const option = (value, groupId, sectionId) => ({ value, dataset: { groupId, sectionId } });
    parent.options = [option('', '', ''), option('unlinked', '', ''), option('group-a', 'a', ''),
        option('section-a1', 'a', 'a1'), option('section-a2', 'a', 'a2'), option('group-b', 'b', '')];
    parent.replaceChildren = (...options) => { parent.options = options; };
    const help = { textContent: 'Choose a matching parent.' };
    const elements = { 'group-id': group, 'section-id': section, 'team-parent-id': parent, 'parent-team-help': help };
    vm.runInNewContext(readFileSync('webroot/js/team-parent-filter.js', 'utf8'), {
        document: { getElementById: id => elements[id] },
    });
    return { group, section, parent, help, ids: () => parent.options.map(option => option.value) };
}

test('parent options follow group and section changes, including group-level parents', () => {
    const ui = setup();
    assert.deepEqual(ui.ids(), ['', 'unlinked']);
    ui.group.value = 'a';
    ui.group.listeners.change();
    assert.deepEqual(ui.ids(), ['', 'group-a']);
    ui.section.value = 'a1';
    ui.section.listeners.change();
    assert.deepEqual(ui.ids(), ['', 'group-a', 'section-a1']);
    ui.parent.value = 'section-a1';
    ui.section.value = 'a2';
    ui.section.listeners.change();
    assert.deepEqual(ui.ids(), ['', 'group-a', 'section-a2']);
    assert.equal(ui.parent.value, '');
    assert.match(ui.help.textContent, /previous parent does not match/);
    ui.group.value = 'b';
    ui.group.listeners.change();
    assert.deepEqual(ui.ids(), ['', 'group-b']);
});

test('opening an existing team retains its parent until the scope changes', () => {
    const ui = setup('group-b', 'a', 'a1');
    assert.equal(ui.parent.value, 'group-b');
    assert.deepEqual(ui.ids(), ['', 'group-a', 'section-a1', 'group-b']);
    assert.match(ui.help.textContent, /current parent is retained/);
    ui.section.value = '';
    ui.section.listeners.change();
    assert.equal(ui.parent.value, '');
    assert.deepEqual(ui.ids(), ['', 'group-a']);
});

test('matching selection survives changes that still permit it', () => {
    const ui = setup('group-a', 'a');
    ui.section.value = 'a1';
    ui.section.listeners.change();
    assert.equal(ui.parent.value, 'group-a');
});
