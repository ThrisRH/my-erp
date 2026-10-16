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
filterOptions('category-type', 'category-parent');

// Transaction category picker modal handling
const categoryPickerModal = document.getElementById('category-picker-modal');
const categoryTrigger = document.querySelector('[data-category-picker-trigger]');
const categoryInput = document.getElementById('category_id');
const transactionTypeSelect = document.getElementById('transaction_type');

function updateCategoryTriggerDisplay() {
  if (!categoryTrigger || !categoryInput) return;
  const selectedId = categoryInput.value;
  const currentOption = categoryPickerModal?.querySelector(`[data-cat-id="${selectedId}"]`);
  const iconTarget = categoryTrigger.querySelector('[data-category-trigger-icon]');
  const nameTarget = categoryTrigger.querySelector('[data-category-trigger-name]');

  if (currentOption) {
    const iconSvg = currentOption.querySelector('.pf-category-icon svg')?.cloneNode(true);
    if (iconTarget) {
      iconTarget.replaceChildren(iconSvg || '');
    }
    if (nameTarget) {
      nameTarget.textContent = currentOption.dataset.catName || '';
    }
    categoryPickerModal?.querySelectorAll('.pf-category-card-option').forEach(btn => {
      const isSelected = btn.dataset.catId === selectedId;
      btn.classList.toggle('is-selected', isSelected);
      btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
    });
  } else {
    // Select first matching category by transaction type if available
    const type = transactionTypeSelect?.value || 'expense';
    const firstMatch = categoryPickerModal?.querySelector(`.pf-category-card-option[data-cat-type="${type}"]:not([hidden])`);
    if (firstMatch) {
      categoryInput.value = firstMatch.dataset.catId;
      updateCategoryTriggerDisplay();
    }
  }
}

function filterCategoryOptions() {
  if (!categoryPickerModal || !transactionTypeSelect) return;
  const currentType = transactionTypeSelect.value;
  const options = categoryPickerModal.querySelectorAll('.pf-category-card-option');
  let hasCurrent = false;

  options.forEach(opt => {
    const matches = opt.dataset.catType === currentType;
    opt.hidden = !matches;
    if (matches && opt.dataset.catId === categoryInput?.value) {
      hasCurrent = true;
    }
  });

  if (!hasCurrent) {
    const firstValid = categoryPickerModal.querySelector(`.pf-category-card-option[data-cat-type="${currentType}"]`);
    if (firstValid && categoryInput) {
      categoryInput.value = firstValid.dataset.catId;
    }
  }
  updateCategoryTriggerDisplay();
}

if (categoryTrigger && categoryPickerModal) {
  categoryTrigger.addEventListener('click', () => {
    filterCategoryOptions();
    categoryPickerModal.showModal();
  });

  categoryPickerModal.addEventListener('click', event => {
    const option = event.target.closest('.pf-category-card-option');
    if (!option) return;
    if (categoryInput) {
      categoryInput.value = option.dataset.catId;
    }
    updateCategoryTriggerDisplay();
    categoryPickerModal.close();
  });

  transactionTypeSelect?.addEventListener('change', () => {
    filterCategoryOptions();
  });

  // Initial trigger setup
  updateCategoryTriggerDisplay();
}

const categoryEditModal = document.getElementById('category-edit-modal');
const categoryDeleteModal = document.getElementById('category-delete-modal');
document.querySelectorAll('[data-category-edit]').forEach(button => {
  button.addEventListener('click', () => {
    if (!categoryEditModal) return;
    categoryEditModal.querySelector('[data-edit-category-id]').value = button.dataset.id;
    categoryEditModal.querySelector('[data-edit-category-name]').value = button.dataset.name;
    categoryEditModal.querySelectorAll('[data-edit-category-icon]').forEach(input => {
      input.checked = input.value === button.dataset.icon;
    });
    const parent = categoryEditModal.querySelector('[data-edit-category-parent]');
    [...parent.options].forEach(option => {
      const unavailable = option.value === button.dataset.id
        || (!!option.dataset.type && option.dataset.type !== button.dataset.type);
      option.hidden = unavailable;
      option.disabled = unavailable;
    });
    parent.value = parent.querySelector(`option[value="${button.dataset.parentId}"]:not([disabled])`)
      ? button.dataset.parentId : '0';
    categoryEditModal.showModal();
  });
});
document.querySelectorAll('[data-category-delete]').forEach(button => {
  button.addEventListener('click', () => {
    if (!categoryDeleteModal) return;
    categoryDeleteModal.querySelector('[data-delete-category-id]').value = button.dataset.id;
    categoryDeleteModal.querySelector('[data-delete-category-name]').textContent = button.dataset.name;
    categoryDeleteModal.showModal();
  });
});

const monthPicker = document.getElementById('month-picker-modal');
const monthInput = document.getElementById('month');
const monthTrigger = document.querySelector('[data-month-picker]');
const currentLocale = () => document.documentElement.lang === 'vi' ? 'vi-VN' : 'en-US';
const monthName = (index) => new Intl.DateTimeFormat(currentLocale(), { month: 'long' })
  .format(new Date(2026, index, 1));
let displayedYear = Number((monthInput?.value || new Date().toISOString()).slice(0, 4));

function renderMonthPicker() {
  if (!monthPicker || !monthInput) return;
  const yearLabel = monthPicker.querySelector('[data-month-year-label]');
  const grid = monthPicker.querySelector('[data-month-grid]');
  if (!yearLabel || !grid) return;
  yearLabel.textContent = displayedYear;
  const selectedMonth = monthInput.value.slice(5, 7);
  grid.replaceChildren(...Array.from({ length: 12 }, (_, index) => {
    const name = monthName(index);
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
  label.textContent = new Intl.DateTimeFormat(currentLocale(), { month: 'long', year: 'numeric' })
    .format(displayedDate);
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
    dateTrigger.querySelector('span')?.replaceChildren(selected.toLocaleDateString(currentLocale()));
    datePicker.close();
  }
});

document.addEventListener('my-erp:locale-change', () => {
  if (monthPicker?.open) renderMonthPicker();
  if (datePicker?.open) renderDatePicker();
});
