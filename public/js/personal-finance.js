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
    opt.style.display = matches ? '' : 'none';
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

const walletEditModal = document.getElementById('wallet-edit-modal');
const walletDeleteModal = document.getElementById('wallet-delete-modal');
document.querySelectorAll('[data-wallet-edit]').forEach(button => {
  button.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!walletEditModal) return;
    walletEditModal.querySelector('[data-edit-wallet-id]').value = button.dataset.id;
    walletEditModal.querySelector('[data-edit-wallet-name]').value = button.dataset.name;
    walletEditModal.showModal();
  });
});
document.querySelectorAll('[data-wallet-delete]').forEach(button => {
  button.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!walletDeleteModal) return;
    walletDeleteModal.querySelector('[data-delete-wallet-id]').value = button.dataset.id;
    walletDeleteModal.querySelector('[data-delete-wallet-name]').textContent = button.dataset.name;
    walletDeleteModal.showModal();
  });
});

// AJAX submission for editing wallet name without URL change or page reload
walletEditModal?.querySelector('form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"], .pf-button-primary');
  if (submitBtn) submitBtn.disabled = true;

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const walletId = String(data.id);
        const newName = data.name;

        // Update card name and dataset in DOM
        document.querySelectorAll(`[data-wallet-edit][data-id="${walletId}"]`).forEach(btn => {
          btn.dataset.name = newName;
          const card = btn.closest('.pf-wallet-card');
          if (card) {
            const nameEl = card.querySelector('.pf-wallet-name');
            if (nameEl) nameEl.textContent = newName;
          }
        });

        document.querySelectorAll(`[data-wallet-delete][data-id="${walletId}"]`).forEach(btn => {
          btn.dataset.name = newName;
        });

        // Update detail header title if open
        const detailTitle = document.getElementById('wallet-detail-title');
        const activeEditBtn = document.querySelector(`.pf-wallet-detail [data-wallet-edit][data-id="${walletId}"]`);
        if (detailTitle && activeEditBtn) {
          detailTitle.textContent = newName;
        }

        // Update wallet dropdown in transaction modal if present
        const walletOption = document.querySelector(`#wallet_id option[value="${walletId}"]`);
        if (walletOption) {
          walletOption.textContent = newName;
        }

        walletEditModal.close();
      } else {
        alert(data.error || 'Could not update wallet');
      }
    } else {
      form.submit();
    }
  } catch (err) {
    form.submit();
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
});

// AJAX submission for deleting wallet without URL change
walletDeleteModal?.querySelector('form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"], .pf-button-danger');
  if (submitBtn) submitBtn.disabled = true;

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const walletId = String(data.id);
        // Remove card from UI
        document.querySelectorAll(`[data-wallet-edit][data-id="${walletId}"]`).forEach(btn => {
          const card = btn.closest('.pf-wallet-card');
          if (card) card.remove();
        });

        // If currently on this wallet detail, redirect to all wallets
        const activeDetail = document.querySelector(`.pf-wallet-detail [data-wallet-delete][data-id="${walletId}"]`);
        if (activeDetail) {
          window.location.href = '/personal-finance?tab=wallets';
          return;
        }

        walletDeleteModal.close();
      } else {
        alert(data.error || 'This wallet is in use and cannot be deleted.');
      }
    } else {
      form.submit();
    }
  } catch (err) {
    form.submit();
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
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

// Live Vietnamese dot thousand separator formatter for currency inputs
function formatCurrencyInput(input) {
  const digitsOnly = input.value.replace(/\D/g, '');
  if (!digitsOnly) {
    input.value = '';
    return;
  }
  // Add dot every 3 digits
  input.value = digitsOnly.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

document.querySelectorAll('input[data-format-currency]').forEach(input => {
  formatCurrencyInput(input);

  input.addEventListener('input', () => {
    const start = input.selectionStart;
    const oldLength = input.value.length;
    formatCurrencyInput(input);
    const newLength = input.value.length;
    // Keep cursor position stable
    const newPosition = Math.max(0, (start || 0) + (newLength - oldLength));
    input.setSelectionRange(newPosition, newPosition);
  });
});

// Strip thousand separator dots before form submission so clean numeric strings are submitted
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', () => {
    form.querySelectorAll('input[data-format-currency]').forEach(input => {
      input.value = input.value.replace(/\./g, '');
    });
  });
});

// Voice Transaction Entry Feature
(() => {
  const voiceBtn = document.getElementById('voice-entry-btn');
  const voiceModal = document.getElementById('voice-modal');
  if (!voiceBtn || !voiceModal) return;

  const micToggle = document.getElementById('voice-mic-toggle');
  const statusText = document.getElementById('voice-status-text');
  const transcriptArea = document.getElementById('voice-transcript-text');
  const feedbackMsg = document.getElementById('voice-feedback-message');
  const confirmBtn = document.getElementById('voice-confirm-parse-btn');

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  let recognition = null;
  let isListening = false;
  let silenceTimer = null;
  const SILENCE_TIMEOUT_MS = 5000; // Stop after 5 seconds of silence

  function resetSilenceTimer() {
    if (silenceTimer) clearTimeout(silenceTimer);
    if (!isListening) return;
    silenceTimer = setTimeout(() => {
      if (isListening) {
        stopListening();
        setStatus('finance.voiceRestart', 'Record again');
      }
    }, SILENCE_TIMEOUT_MS);
  }

  function clearSilenceTimer() {
    if (silenceTimer) {
      clearTimeout(silenceTimer);
      silenceTimer = null;
    }
  }

  function t(key, fallback) {
    const locale = window.localStorage.getItem('my-erp-locale') || 'vi';
    return (window.appPreferences && window.appPreferences.t) ? window.appPreferences.t(key) : fallback;
  }

  function setStatus(msgKey, defaultMsg) {
    if (statusText) {
      statusText.setAttribute('data-i18n', msgKey);
      statusText.textContent = t(msgKey, defaultMsg);
    }
  }

  function showFeedback(text, isWarning = false) {
    if (!feedbackMsg) return;
    feedbackMsg.hidden = false;
    feedbackMsg.className = `pf-voice-feedback ${isWarning ? 'is-warning' : 'is-info'}`;
    feedbackMsg.textContent = text;
  }

  function clearFeedback() {
    if (feedbackMsg) {
      feedbackMsg.hidden = true;
      feedbackMsg.textContent = '';
    }
  }

  let finalTranscriptAccumulator = '';

  if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'vi-VN';

    recognition.onstart = () => {
      isListening = true;
      micToggle?.classList.add('is-listening');
      setStatus('finance.voiceListening', 'Listening... please speak your transaction');
      clearFeedback();
      resetSilenceTimer();
    };

    recognition.onresult = event => {
      resetSilenceTimer();
      let interimTranscript = '';
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscriptAccumulator += (finalTranscriptAccumulator ? ' ' : '') + event.results[i][0].transcript.trim();
        } else {
          interimTranscript += event.results[i][0].transcript;
        }
      }
      transcriptArea.value = (finalTranscriptAccumulator + (interimTranscript ? ' ' + interimTranscript : '')).trim();
    };

    recognition.onerror = event => {
      clearSilenceTimer();
      isListening = false;
      micToggle?.classList.remove('is-listening');
      if (event.error === 'no-speech') {
        setStatus('finance.voiceNoSpeech', 'No speech detected, please try again.');
      } else {
        setStatus('finance.voiceRestart', 'Record again');
      }
    };

    recognition.onend = () => {
      clearSilenceTimer();
      isListening = false;
      micToggle?.classList.remove('is-listening');
      if (transcriptArea.value.trim().length > 0) {
        setStatus('finance.voiceRestart', 'Record again');
      } else {
        setStatus('finance.voiceInstruction', 'Say what you spent or earned');
      }
    };
  }

  function startListening() {
    if (!recognition) {
      showFeedback(t('finance.voiceNotSupported', 'Speech recognition not supported in this browser. Please type directly.'), true);
      return;
    }
    try {
      finalTranscriptAccumulator = '';
      recognition.lang = (window.localStorage.getItem('my-erp-locale') || 'vi') === 'vi' ? 'vi-VN' : 'en-US';
      recognition.start();
    } catch (e) {
      // If already started, ignore
    }
  }

  function stopListening() {
    clearSilenceTimer();
    if (recognition && isListening) {
      recognition.stop();
    }
  }

  voiceBtn.addEventListener('click', () => {
    finalTranscriptAccumulator = '';
    transcriptArea.value = '';
    clearFeedback();
    voiceModal.showModal();
    startListening();
  });

  micToggle?.addEventListener('click', () => {
    if (isListening) {
      stopListening();
    } else {
      startListening();
    }
  });

  voiceModal.addEventListener('close', () => {
    stopListening();
  });

  // Convert unit words to raw integer amount (e.g. "10k" -> 10000, "1.5 triệu" -> 1500000)
  function parseSingleAmountValue(str) {
    if (!str) return 0;
    const clean = str.trim().toLowerCase();

    // Check million (triệu, tr, m)
    const mMatch = clean.match(/^(\d+(?:[.,]\d+)?)\s*(?:triệu|tr|m|million)\s*(\d+)?$/i);
    if (mMatch) {
      const base = parseFloat(mMatch[1].replace(',', '.'));
      let extra = 0;
      if (mMatch[2]) {
        const extraNum = parseInt(mMatch[2], 10);
        if (extraNum < 10) extra = extraNum * 100000;
        else if (extraNum < 100) extra = extraNum * 10000;
        else if (extraNum < 1000) extra = extraNum * 1000;
        else extra = extraNum;
      }
      return Math.round(base * 1000000 + extra);
    }

    // Check thousand (nghìn, ngàn, k, thousand)
    const kMatch = clean.match(/^(\d+(?:[.,]\d+)?)\s*(?:nghìn|ngàn|k|thousand)$/i);
    if (kMatch) {
      const num = parseFloat(kMatch[1].replace(',', '.'));
      return Math.round(num * 1000);
    }

    // Standard raw digits
    const digitsMatch = clean.match(/^(\d{1,3}(?:[.,]\d{3})*|\d+)$/);
    if (digitsMatch) {
      return parseInt(digitsMatch[1].replace(/[.,]/g, ''), 10) || 0;
    }

    return 0;
  }

  // Vietnamese & English Natural Language Transaction Parser
  function parseTransactionText(rawText) {
    const text = rawText.toLowerCase().trim();
    if (!text) return null;

    // 1. Transaction Type Detection
    let type = 'expense';
    const isIncome = /\b(thu|thu nhập|nhận|tiền lương|lương|thưởng|kiếm được|chuyển vào|bán được|income|received|salary|earned)\b/i.test(text);
    const isExpense = /\b(chi|tiêu|tốn|mua|trả|ăn|uống|hết|mất|đóng|nộp|spend|spent|buy|bought|paid|expense)\b/i.test(text);

    if (isIncome && !isExpense) {
      type = 'income';
    } else {
      type = 'expense';
    }

    // 2. Amount Extraction & Arithmetic Calculation
    // Supports math phrases like:
    // - "10k nhân cho 3 lần", "10 nghìn nhân 3", "20k x 4", "50 nghìn nhân 2 lần"
    // - "10k cộng 20k", "50k trừ 10k"
    let amount = 0;

    // Case 0: Arithmetic multiplication expression: [Amount] x / nhân / nhân cho / nhân với [Multiplier] (lần)?
    // Example: "10k nhân cho 3 lần" -> 10000 * 3 = 30000
    const mathMulMatch = text.match(/(\d+(?:[.,]\d+)?\s*(?:k|nghìn|ngàn|triệu|tr|m)?)\s*(?:nhân cho|nhân với|nhân|\*|x)\s*(\d+)\s*(?:lần|cái|bữa|chuyến|vé|phần|bát|ly|cốc)?/i);
    if (mathMulMatch) {
      const unitAmount = parseSingleAmountValue(mathMulMatch[1]);
      const multiplier = parseInt(mathMulMatch[2], 10);
      if (unitAmount > 0 && multiplier > 0) {
        amount = unitAmount * multiplier;
      }
    }

    // Case 0b: Arithmetic addition expression: [Amount] cộng / + [Amount]
    // Example: "10k cộng 20k" -> 30000
    if (!amount) {
      const mathAddMatch = text.match(/(\d+(?:[.,]\d+)?\s*(?:k|nghìn|ngàn|triệu|tr|m)?)\s*(?:cộng với|cộng|\+)\s*(\d+(?:[.,]\d+)?\s*(?:k|nghìn|ngàn|triệu|tr|m)?)/i);
      if (mathAddMatch) {
        const val1 = parseSingleAmountValue(mathAddMatch[1]);
        const val2 = parseSingleAmountValue(mathAddMatch[2]);
        if (val1 > 0 && val2 > 0) {
          amount = val1 + val2;
        }
      }
    }

    // Case 0c: Arithmetic subtraction expression: [Amount] trừ / - [Amount]
    // Example: "50k trừ 10k" -> 40000
    if (!amount) {
      const mathSubMatch = text.match(/(\d+(?:[.,]\d+)?\s*(?:k|nghìn|ngàn|triệu|tr|m)?)\s*(?:trừ đi|trừ|\-)\s*(\d+(?:[.,]\d+)?\s*(?:k|nghìn|ngàn|triệu|tr|m)?)/i);
      if (mathSubMatch) {
        const val1 = parseSingleAmountValue(mathSubMatch[1]);
        const val2 = parseSingleAmountValue(mathSubMatch[2]);
        if (val1 > val2) {
          amount = val1 - val2;
        }
      }
    }

    // Case A: Trieu / Million: e.g. "1 triệu 2", "1.5 triệu", "2 triệu 500"
    if (!amount) {
      const millionMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:triệu|tr|m|million)\s*(\d+)?/i);
      if (millionMatch) {
        amount = parseSingleAmountValue(millionMatch[0]);
      }
    }

    // Case B: Nghin / K / Thousand: e.g. "120 nghìn", "120k", "120 ngàn"
    if (!amount) {
      const thousandMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:nghìn|ngàn|k|thousand)/i);
      if (thousandMatch) {
        amount = parseSingleAmountValue(thousandMatch[0]);
      }
    }

    // Case C: Standard formatted number: e.g. "120.000" or "120,000" or raw "120000"
    if (!amount) {
      const standardMatch = text.match(/(\d{1,3}(?:[.,]\d{3})+(?!\d)|\b\d{4,9}\b)/);
      if (standardMatch) {
        amount = parseInt(standardMatch[1].replace(/[.,]/g, ''), 10);
      }
    }

    // 3. Date Extraction
    let occurredOn = new Date().toISOString().slice(0, 10);
    if (/\b(hôm qua|yesterday)\b/i.test(text)) {
      const d = new Date();
      d.setDate(d.getDate() - 1);
      occurredOn = d.toISOString().slice(0, 10);
    } else if (/\b(hôm kia)\b/i.test(text)) {
      const d = new Date();
      d.setDate(d.getDate() - 2);
      occurredOn = d.toISOString().slice(0, 10);
    } else {
      // Date pattern: "ngày DD/MM" or "ngày DD tháng MM"
      const dateMatch = text.match(/ngày\s+(\d{1,2})(?:[\/\-]|\s+tháng\s+)(\d{1,2})(?:[\/\-]|\s+năm\s+)?(\d{4})?/i);
      if (dateMatch) {
        const day = dateMatch[1].padStart(2, '0');
        const m = dateMatch[2].padStart(2, '0');
        const y = dateMatch[3] || new Date().getFullYear();
        occurredOn = `${y}-${m}-${day}`;
      }
    }

    // 4. Category Matching against rendered category cards
    const categoryCards = Array.from(document.querySelectorAll('#category-picker-modal .pf-category-card-option'));
    let matchedCategory = null;

    // Filter categories that match candidate transaction type
    const typedCards = categoryCards.filter(c => c.dataset.catType === type);

    // Score cards by substring match with card name
    let bestScore = 0;
    for (const card of typedCards) {
      const name = (card.dataset.catName || '').toLowerCase().trim();
      if (!name) continue;

      // Exact substring contained in speech: e.g. "ăn uống" in "tôi đã chi tiêu cho ăn uống"
      if (text.includes(name)) {
        const score = name.length * 2;
        if (score > bestScore) {
          bestScore = score;
          matchedCategory = card;
        }
      } else {
        // Individual word overlap
        const words = name.split(/\s+/).filter(w => w.length > 1);
        let matchCount = 0;
        for (const w of words) {
          if (text.includes(w)) matchCount++;
        }
        if (matchCount > 0 && matchCount === words.length && words.length > bestScore) {
          bestScore = words.length;
          matchedCategory = card;
        }
      }
    }

    // Common synonyms mapping if name was not directly matched
    if (!matchedCategory) {
      const synonyms = [
        { keys: ['ăn', 'uống', 'cơm', 'trưa', 'tối', 'sáng', 'cafe', 'cà phê', 'trà sữa', 'nhậu', 'food', 'drink'], targetWords: ['ăn uống', 'food', 'dining'] },
        { keys: ['xăng', 'xe', 'grab', 'taxi', 'gửi xe', 'bảo dưỡng xe', 'đi lại', 'transport'], targetWords: ['đi lại', 'di chuyển', 'xăng', 'transport'] },
        { keys: ['điện', 'nước', 'internet', 'wifi', 'tiền nhà', 'thuê nhà', 'bills'], targetWords: ['hóa đơn', 'nhà', 'tiện ích', 'bills'] },
        { keys: ['mua sắm', 'quần áo', 'giày', 'shopping'], targetWords: ['mua sắm', 'shopping', 'quần áo'] },
        { keys: ['lương', 'salary', 'thu nhập chính'], targetWords: ['lương', 'salary'] },
        { keys: ['thưởng', 'bonus'], targetWords: ['thưởng', 'bonus'] }
      ];

      for (const syn of synonyms) {
        if (syn.keys.some(k => text.includes(k))) {
          matchedCategory = typedCards.find(card => {
            const cName = (card.dataset.catName || '').toLowerCase();
            return syn.targetWords.some(tw => cName.includes(tw));
          });
          if (matchedCategory) break;
        }
      }
    }

    // 5. Notes / Description
    // Clean raw text to keep a readable note
    let cleanNotes = rawText.trim();
    // Capitalize first letter
    cleanNotes = cleanNotes.charAt(0).toUpperCase() + cleanNotes.slice(1);

    return {
      type,
      amount,
      occurredOn,
      category: matchedCategory,
      notes: cleanNotes
    };
  }

  confirmBtn?.addEventListener('click', () => {
    stopListening();
    const transcript = transcriptArea.value.trim();
    if (!transcript) {
      showFeedback(t('finance.voiceNoSpeech', 'Please speak or enter text first.'), true);
      return;
    }

    const parsed = parseTransactionText(transcript);
    if (!parsed) {
      showFeedback(t('finance.voiceNoSpeech', 'Could not parse transaction details.'), true);
      return;
    }

    // Populate transaction form in #transaction-modal
    const txModal = document.getElementById('transaction-modal');
    if (!txModal) return;

    // Set transaction type
    const typeSelect = document.getElementById('transaction_type');
    if (typeSelect) {
      typeSelect.value = parsed.type;
      typeSelect.dispatchEvent(new Event('change'));
    }

    // Set amount
    const amountInput = txModal.querySelector('input[name="amount"]');
    if (amountInput && parsed.amount > 0) {
      amountInput.value = parsed.amount.toString();
      formatCurrencyInput(amountInput);
    }

    // Set date
    const dateInput = document.getElementById('occurred_on');
    const dateTrigger = document.querySelector('[data-date-picker]');
    if (dateInput && parsed.occurredOn) {
      dateInput.value = parsed.occurredOn;
      if (dateTrigger) {
        const d = new Date(`${parsed.occurredOn}T12:00:00`);
        const locale = (window.localStorage.getItem('my-erp-locale') || 'vi') === 'vi' ? 'vi-VN' : 'en-US';
        dateTrigger.querySelector('span')?.replaceChildren(d.toLocaleDateString(locale));
      }
    }

    // Set category
    if (parsed.category) {
      const catId = parsed.category.dataset.catId;
      const catInput = document.getElementById('category_id');
      if (catInput) {
        catInput.value = catId;
        updateCategoryTriggerDisplay();
      }
    }

    // Set notes
    const notesInput = txModal.querySelector('textarea[name="notes"]');
    if (notesInput) {
      notesInput.value = parsed.notes;
    }

    // Close voice modal and open transaction confirmation modal
    voiceModal.close();
    txModal.showModal();
  });
})();
