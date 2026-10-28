import { openModal, closeModal } from '../modal.js';

const MODAL_ID = 'calculator-modal';
const mobileQuery = window.matchMedia('(max-width: 767px)');
const symbols = { '*': '×', '/': '÷', '-': '−', '+': '+' };

const modal = document.getElementById(MODAL_ID);
const expressionEl = modal?.querySelector('[data-calc-expression]');
const resultEl = modal?.querySelector('[data-calc-result]');

let target = null;
let expression = '';
const history = [];
const HISTORY_LIMIT = 10;
const historyEl = modal?.querySelector('[data-calc-history]');
const historyEmptyEl = modal?.querySelector('[data-calc-history-empty]');

/** "1250000" to "1.250.000", keeping a leading minus. */
function group(digits) {
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function prettify(source) {
    return source
        .replace(/\d+/g, group)
        .replace(/[-+*/]/g, (operator) => ` ${symbols[operator]} `)
        .trim();
}

function remember(source, result) {
    history.unshift({ source, result });
    history.length = Math.min(history.length, HISTORY_LIMIT);
    renderHistory();
}

function renderHistory() {
    historyEmptyEl.hidden = history.length > 0;
    historyEl.replaceChildren(
        ...history.map(({ source, result }, index) => {
            const item = document.createElement('li');
            const button = document.createElement('button');

            button.type = 'button';
            button.dataset.calcHistory = index;
            button.className = 'w-full rounded-md px-3 py-2 text-right font-body text-body-md text-on-surface hover:bg-surface-container';
            button.textContent = `${prettify(source)} = ${group(String(result))}`;
            item.append(button);

            return item;
        }),
    );
}

/**
 * Evaluate "12+3*4" without eval(). Returns null for an incomplete expression
 * and NaN for division by zero.
 */
export function evaluate(source) {
    const tokens = source.match(/\d+\.?\d*|\.\d+|[-+*/]/g) ?? [];

    if (tokens[0] === '-' && tokens.length > 1) {
        tokens.shift();
        tokens[0] = `-${tokens[0]}`;
    }

    if (tokens.length === 0 || tokens.length % 2 === 0) {
        return null;
    }

    const isOperator = (token, index) => index % 2 === 1 && /^[-+*/]$/.test(token);
    if (tokens.some((token, index) => (index % 2 === 1) !== isOperator(token, index))) {
        return null;
    }

    const terms = [Number(tokens[0])];
    const adds = [];

    for (let i = 1; i < tokens.length; i += 2) {
        const operator = tokens[i];
        const value = Number(tokens[i + 1]);

        if (operator === '*') {
            terms[terms.length - 1] *= value;
        } else if (operator === '/') {
            if (value === 0) {
                return NaN;
            }
            terms[terms.length - 1] /= value;
        } else {
            adds.push(operator);
            terms.push(value);
        }
    }

    const total = terms.reduce((sum, term, index) => (adds[index - 1] === '-' ? sum - term : sum + term));

    return Math.round(total);
}

function render() {
    const result = evaluate(expression);

    expressionEl.textContent = prettify(expression) || '0';
    resultEl.textContent = result === null ? '' : Number.isNaN(result) ? '—' : `= ${group(String(result))}`;
}

function press(key) {
    if (key === 'clear') {
        expression = '';
    } else if (key === 'back') {
        expression = expression.slice(0, -1);
    } else if (key === 'equals') {
        const result = evaluate(expression);

        if (result !== null && !Number.isNaN(result)) {
            if (/[-+*/]/.test(expression.slice(1))) {
                remember(expression, result);
            }
            expression = String(result);
        }
    } else if (/^[-+*/]$/.test(key)) {
        if (expression === '' && key !== '-') {
            return;
        }
        expression = /[-+*/]$/.test(expression) && expression.length > 1 ? expression.slice(0, -1) + key : expression + key;
    } else {
        expression += key;
    }

    render();
}

function apply() {
    const result = evaluate(expression === '' ? '0' : expression);

    if (result === null || Number.isNaN(result)) {
        return;
    }

    if (/[-+*/]/.test(expression.slice(1))) {
        remember(expression, result);
    }

    target.value = String(result);
    target.dispatchEvent(new Event('input', { bubbles: true }));
    target.dispatchEvent(new Event('change', { bubbles: true }));
    closeModal(MODAL_ID);
}

function openFor(input) {
    target = input;
    expression = input.value;
    input.blur();
    render();
    renderHistory();
    openModal(MODAL_ID);
}

if (modal) {
    modal.addEventListener('click', (event) => {
        const key = event.target.closest('[data-calc-key]')?.dataset.calcKey;

        const entry = event.target.closest('[data-calc-history]');

        if (key) {
            press(key);
        } else if (entry) {
            expression = String(history[entry.dataset.calcHistory].result);
            render();
        } else if (event.target.closest('[data-calc-apply]')) {
            apply();
        }
    });

    ['focusin', 'click'].forEach((type) => {
        document.addEventListener(type, (event) => {
            const input = event.target.closest?.('input[data-calculator]');

            if (input && mobileQuery.matches && !input.readOnly && !input.disabled) {
                openFor(input);
            }
        });
    });
}
