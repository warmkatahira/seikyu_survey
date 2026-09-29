/**
 * Turns a `<select data-searchable>` into a type-to-filter combobox.
 *
 * The original select stays in the DOM and remains the form control, so names, values,
 * server-side validation and `old()` repopulation all keep working, and the page still
 * functions as plain selects when this script does not run.
 */

const PANEL_CLASSES =
    'absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-slate-300 bg-white py-1 shadow-lg';
const INPUT_CLASSES =
    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500';
const OPTION_CLASSES = 'cursor-pointer px-3 py-2 text-sm text-slate-800';
const ACTIVE_CLASSES = 'bg-slate-100';
const GROUP_CLASSES = 'bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700';

let comboboxCount = 0;

/** Lowercases and drops spaces so "山田 太郎" is found by typing "山田太郎". */
function normalize(value) {
    return value.toLowerCase().replace(/[\s　]+/g, '');
}

/** The label of the `<optgroup>` an option sits in, or '' when it is not grouped. */
function groupOf(option) {
    return option.parentElement instanceof HTMLOptGroupElement ? option.parentElement.label : '';
}

function createCombobox(select) {
    const id = `combobox-${++comboboxCount}`;
    const options = Array.from(select.options);
    const placeholder = options.find((option) => option.value === '')?.textContent.trim() ?? '';

    const wrapper = document.createElement('div');
    wrapper.className = 'relative';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = INPUT_CLASSES;
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.placeholder = placeholder || '入力して検索';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', id);
    input.setAttribute('aria-autocomplete', 'list');

    const labelFor = select.id ? document.querySelector(`label[for="${select.id}"]`) : null;
    if (labelFor) {
        input.id = `${select.id}-search`;
        labelFor.setAttribute('for', input.id);
    }

    const panel = document.createElement('ul');
    panel.id = id;
    panel.className = PANEL_CLASSES;
    panel.setAttribute('role', 'listbox');
    panel.hidden = true;

    select.classList.add('hidden');
    select.setAttribute('tabindex', '-1');
    select.setAttribute('aria-hidden', 'true');
    select.insertAdjacentElement('afterend', wrapper);
    wrapper.append(input, panel);

    let activeIndex = -1;
    let matches = [];

    const selectedLabel = () => {
        const option = select.selectedOptions[0];

        if (! option?.value) {
            return '';
        }

        const group = groupOf(option);

        return group ? `${option.textContent.trim()}（${group}）` : option.textContent.trim();
    };

    function render(query) {
        const needle = normalize(query);

        matches = options.filter((option) => {
            if (option.value === '') {
                // The blank option is offered only when it can actually clear a choice.
                return needle === '' && select.value !== '';
            }

            // The group label is searchable too, so typing an office name lists its staff.
            return needle === '' || normalize(option.textContent + groupOf(option)).includes(needle);
        });

        panel.replaceChildren();

        if (matches.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'px-3 py-2 text-sm text-slate-500';
            empty.textContent = '該当する候補がありません';
            panel.append(empty);

            return;
        }

        let currentGroup = '';

        matches.forEach((option, index) => {
            const group = groupOf(option);

            if (group && group !== currentGroup) {
                const heading = document.createElement('li');
                heading.className = GROUP_CLASSES;
                heading.setAttribute('role', 'presentation');
                heading.textContent = group;
                panel.append(heading);
            }

            currentGroup = group;

            const item = document.createElement('li');
            item.id = `${id}-option-${index}`;
            item.className = OPTION_CLASSES;
            item.setAttribute('role', 'option');
            item.dataset.index = String(index);
            item.textContent = option.value === ''
                ? `（選択を解除）`
                : option.textContent.trim();

            if (option.value === select.value) {
                item.setAttribute('aria-selected', 'true');
                item.classList.add('font-medium');
            }

            panel.append(item);
        });

        setActive(matches.findIndex((option) => option.value === select.value && option.value !== ''));
    }

    function setActive(index) {
        activeIndex = index;

        panel.querySelectorAll('[data-index]').forEach((item) => {
            const isActive = Number(item.dataset.index) === activeIndex;
            item.classList.toggle(ACTIVE_CLASSES, isActive);

            if (isActive) {
                input.setAttribute('aria-activedescendant', item.id);
                item.scrollIntoView({ block: 'nearest' });
            }
        });

        if (activeIndex < 0) {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function open() {
        if (! panel.hidden) {
            return;
        }

        render(input.value === selectedLabel() ? '' : input.value);
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function close() {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        input.value = selectedLabel();
    }

    function choose(index) {
        const option = matches[index];

        if (! option) {
            return;
        }

        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    }

    input.value = selectedLabel();

    input.addEventListener('focus', () => {
        input.select();
        open();
    });

    // A click on the already-focused box (after a choice) reopens the list; focus alone won't fire.
    input.addEventListener('click', open);

    input.addEventListener('input', () => {
        open();
        render(input.value);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            open();

            if (matches.length > 0) {
                const step = event.key === 'ArrowDown' ? 1 : -1;
                setActive((activeIndex + step + matches.length) % matches.length);
            }

            return;
        }

        if (event.key === 'Enter' && ! panel.hidden) {
            event.preventDefault();
            choose(activeIndex < 0 && matches.length === 1 ? 0 : activeIndex);

            return;
        }

        if (event.key === 'Escape') {
            close();
        }
    });

    // mousedown rather than click, so the choice registers before the input loses focus.
    // Every mousedown is swallowed so clicking a group heading does not close the list.
    panel.addEventListener('mousedown', (event) => {
        event.preventDefault();

        const item = event.target.closest('[data-index]');

        if (item) {
            choose(Number(item.dataset.index));
        }
    });

    wrapper.addEventListener('focusout', (event) => {
        if (! wrapper.contains(event.relatedTarget)) {
            close();
        }
    });

    // Keeps the box in step when something else sets the value, such as the customer
    // field filling in the office it belongs to.
    select.addEventListener('change', () => {
        if (panel.hidden) {
            input.value = selectedLabel();
        }
    });
}

export function initSearchableSelects(root = document) {
    root.querySelectorAll('select[data-searchable]').forEach((select) => {
        if (! select.dataset.searchableReady) {
            select.dataset.searchableReady = 'true';
            createCombobox(select);
        }
    });
}
