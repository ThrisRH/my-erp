document.querySelectorAll('[data-modal-open]').forEach(button => {
  button.addEventListener('click', () => document.getElementById(button.dataset.modalOpen)?.showModal());
});
document.querySelectorAll('[data-modal-close]').forEach(button => {
  button.addEventListener('click', () => button.closest('dialog')?.close());
});
document.querySelectorAll('dialog').forEach(dialog => {
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
});
function filterOptions(typeInput, selectInput) {
  const type = document.getElementById(typeInput);
  const select = document.getElementById(selectInput);
  if (!type || !select) return;
  const update = () => {
    [...select.options].forEach(option => {
      option.hidden = !!option.dataset.type && option.dataset.type !== type.value;
      option.disabled = option.hidden;
    });
    if (select.selectedOptions[0]?.disabled) {
      const firstEnabled = [...select.options].find(option => !option.disabled);
      if (firstEnabled) select.value = firstEnabled.value;
    }
  };
  type.addEventListener('change', update);
  update();
}
filterOptions('transaction_type', 'category_id');
filterOptions('category-type', 'category-parent');

const monthPicker = document.getElementById('month-picker-modal');
const monthInput = document.getElementById('month');
const monthTrigger = document.querySelector('[data-month-picker]');
const monthNames = [
  'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
  'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12',
];
let displayedYear = Number((monthInput?.value || new Date().toISOString()).slice(0, 4));

function renderMonthPicker() {
  if (!monthPicker || !monthInput) return;
  const yearLabel = monthPicker.querySelector('[data-month-year-label]');
  const grid = monthPicker.querySelector('[data-month-grid]');
  if (!yearLabel || !grid) return;
  yearLabel.textContent = displayedYear;
  const selectedMonth = monthInput.value.slice(5, 7);
  grid.replaceChildren(...monthNames.map((name, index) => {
    const value = `${displayedYear}-${String(index + 1).padStart(2, '0')}`;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `pf-month-option${value === monthInput.value ? ' is-selected' : ''}`;
    button.textContent = name;
    button.dataset.monthValue = value;
    button.setAttribute('aria-pressed', value === `${displayedYear}-${selectedMonth}` ? 'true' : 'false');
    return button;
  }));
}

monthTrigger?.addEventListener('click', () => {
  displayedYear = Number((monthInput?.value || '').slice(0, 4)) || new Date().getFullYear();
  renderMonthPicker();
  monthPicker?.showModal();
});
monthPicker?.addEventListener('click', event => {
  const target = event.target.closest('[data-month-value], [data-month-year]');
  if (!target) return;
  if (target.dataset.monthYear) {
    displayedYear += target.dataset.monthYear === 'previous' ? -1 : 1;
    renderMonthPicker();
    return;
  }
  if (target.dataset.monthValue && monthInput && monthTrigger) {
    monthInput.value = target.dataset.monthValue;
    monthTrigger.querySelector('span')?.replaceChildren(`${target.textContent} ${displayedYear}`);
    monthPicker.close();
  }
});

const datePicker = document.getElementById('date-picker-modal');
const dateInput = document.getElementById('occurred_on');
const dateTrigger = document.querySelector('[data-date-picker]');
let displayedDate = dateInput?.value ? new Date(`${dateInput.value}T12:00:00`) : new Date();

function formatDateValue(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function renderDatePicker() {
  if (!datePicker || !dateInput) return;
  const label = datePicker.querySelector('[data-date-label]');
  const grid = datePicker.querySelector('[data-date-grid]');
  if (!label || !grid) return;
  label.textContent = `Tháng ${displayedDate.getMonth() + 1}, ${displayedDate.getFullYear()}`;
  const firstDay = new Date(displayedDate.getFullYear(), displayedDate.getMonth(), 1);
  const daysInMonth = new Date(displayedDate.getFullYear(), displayedDate.getMonth() + 1, 0).getDate();
  const leadingDays = (firstDay.getDay() + 6) % 7;
  const selectedValue = dateInput.value;
  const cells = Array.from({ length: leadingDays }, () => {
    const blank = document.createElement('span');
    blank.className = 'pf-date-blank';
    return blank;
  });
  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(displayedDate.getFullYear(), displayedDate.getMonth(), day, 12);
    const value = formatDateValue(date);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `pf-date-option${value === selectedValue ? ' is-selected' : ''}`;
    button.dataset.dateValue = value;
    button.textContent = day;
    button.setAttribute('aria-pressed', value === selectedValue ? 'true' : 'false');
    cells.push(button);
  }
  grid.replaceChildren(...cells);
}

dateTrigger?.addEventListener('click', () => {
  displayedDate = dateInput?.value ? new Date(`${dateInput.value}T12:00:00`) : new Date();
  renderDatePicker();
  datePicker?.showModal();
});
datePicker?.addEventListener('click', event => {
  const target = event.target.closest('[data-date-value], [data-date-month]');
  if (!target) return;
  if (target.dataset.dateMonth) {
    displayedDate = new Date(displayedDate.getFullYear(), displayedDate.getMonth() + (target.dataset.dateMonth === 'previous' ? -1 : 1), 1, 12);
    renderDatePicker();
    return;
  }
  if (target.dataset.dateValue && dateInput && dateTrigger) {
    dateInput.value = target.dataset.dateValue;
    const selected = new Date(`${target.dataset.dateValue}T12:00:00`);
    dateTrigger.querySelector('span')?.replaceChildren(selected.toLocaleDateString('vi-VN'));
    datePicker.close();
  }
});
