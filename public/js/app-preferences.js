(() => {
  const storage = {
    theme: 'my-erp-theme',
    locale: 'my-erp-locale',
  };

  const messages = {
    vi: {
      'app.navigation': 'ỨNG DỤNG',
      'app.overview': 'Tổng quan',
      'app.personalFinance': 'Thu chi cá nhân',
      'app.theme': 'Giao diện',
      'app.language': 'Ngôn ngữ',
      'finance.title': 'Thu chi cá nhân',
      'finance.subtitle': 'Theo dõi dòng tiền, ngân sách và xu hướng chi tiêu của bạn.',
      'finance.month': 'Tháng',
      'finance.view': 'Xem',
      'finance.filter': 'Lọc',
      'finance.addTransaction': '+ Thêm giao dịch',
      'finance.transactions': 'Giao dịch',
      'finance.budget': 'Ngân sách',
      'finance.categories': 'Danh mục',
      'finance.transactionType': 'Loại giao dịch',
      'finance.category': 'Danh mục',
      'finance.wallet': 'Ví',
      'finance.amount': 'Số tiền (₫)',
      'finance.date': 'Ngày giao dịch',
      'finance.notes': 'Ghi chú',
      'finance.cancel': 'Hủy',
      'finance.saveTransaction': 'Lưu giao dịch',
      'finance.addTransactionTitle': 'Thêm giao dịch',
      'finance.editTransactionTitle': 'Sửa giao dịch',
      'finance.expense': 'Chi',
      'finance.income': 'Thu',
      'finance.balance': 'Dòng tiền còn lại',
      'finance.balanceDescription': 'Tổng thu trừ tổng chi trong tháng đang xem.',
      'finance.moneyIn': 'Tiền vào',
      'finance.moneyOut': 'Tiền ra',
      'finance.monthlyAnalysis': 'Phân tích tháng',
      'finance.yearlyView': 'Góc nhìn cả năm',
      'finance.weeklyTrend': 'Theo tuần trong tháng',
      'finance.monthlyTrend': 'Theo tháng trong năm',
      'finance.comparePrevious': 'So kỳ trước',
      'finance.compareFirst': 'So kỳ đầu',
      'finance.history': 'LỊCH SỬ',
      'finance.noNotes': 'Không có ghi chú',
      'finance.edit': 'Sửa',
      'finance.plan': 'KẾ HOẠCH',
      'finance.copyPreviousMonth': 'Sao chép tháng trước',
      'finance.saveBudget': 'Lưu ngân sách',
      'finance.classification': 'PHÂN LOẠI',
      'finance.createCategory': '+ Tạo danh mục',
      'finance.budgetForMonth': 'Ngân sách tháng',
      'finance.categoryListTitle': 'Danh mục thu chi',
      'finance.week': 'Tuần',
      'finance.noData': 'Chưa có dữ liệu trong kỳ này.',
      'finance.spendingIncreased': 'Chi tiêu tăng',
      'finance.spendingDecreased': 'Chi tiêu giảm',
    },
    en: {
      'app.navigation': 'APPLICATIONS',
      'app.overview': 'Overview',
      'app.personalFinance': 'Personal finance',
      'app.theme': 'Theme',
      'app.language': 'Language',
      'finance.title': 'Personal finance',
      'finance.subtitle': 'Track your cash flow, budgets, and spending trends.',
      'finance.month': 'Month',
      'finance.view': 'View',
      'finance.filter': 'Filter',
      'finance.addTransaction': '+ Add transaction',
      'finance.transactions': 'Transactions',
      'finance.budget': 'Budget',
      'finance.categories': 'Categories',
      'finance.transactionType': 'Transaction type',
      'finance.category': 'Category',
      'finance.wallet': 'Wallet',
      'finance.amount': 'Amount (₫)',
      'finance.date': 'Transaction date',
      'finance.notes': 'Notes',
      'finance.cancel': 'Cancel',
      'finance.saveTransaction': 'Save transaction',
      'finance.addTransactionTitle': 'Add transaction',
      'finance.editTransactionTitle': 'Edit transaction',
      'finance.expense': 'Expense',
      'finance.income': 'Income',
      'finance.balance': 'Available cash flow',
      'finance.balanceDescription': 'Income minus expenses for the selected month.',
      'finance.moneyIn': 'Money in',
      'finance.moneyOut': 'Money out',
      'finance.monthlyAnalysis': 'Monthly analysis',
      'finance.yearlyView': 'Year at a glance',
      'finance.weeklyTrend': 'Weekly trend',
      'finance.monthlyTrend': 'Monthly trend',
      'finance.comparePrevious': 'vs previous period',
      'finance.compareFirst': 'vs first period',
      'finance.history': 'HISTORY',
      'finance.noNotes': 'No notes',
      'finance.edit': 'Edit',
      'finance.plan': 'PLAN',
      'finance.copyPreviousMonth': 'Copy previous month',
      'finance.saveBudget': 'Save budget',
      'finance.classification': 'CLASSIFICATION',
      'finance.createCategory': '+ Create category',
      'finance.budgetForMonth': 'Monthly budget',
      'finance.categoryListTitle': 'Income and expense categories',
      'finance.week': 'Week',
      'finance.noData': 'No data for this period.',
      'finance.spendingIncreased': 'Spending increased',
      'finance.spendingDecreased': 'Spending decreased',
    },
  };

  const root = document.documentElement;
  const translate = (locale) => {
    const dictionary = messages[locale] || messages.vi;
    document.querySelectorAll('[data-i18n]').forEach((element) => {
      const message = dictionary[element.dataset.i18n];
      if (message) element.textContent = message;
    });
    document.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
      const message = dictionary[element.dataset.i18nAriaLabel];
      if (message) element.setAttribute('aria-label', message);
    });
    root.lang = locale;
    document.dispatchEvent(new CustomEvent('my-erp:locale-change', { detail: { locale } }));
  };

  const setTheme = (theme) => {
    root.dataset.theme = theme;
    localStorage.setItem(storage.theme, theme);
  };

  const setLocale = (locale) => {
    localStorage.setItem(storage.locale, locale);
    translate(locale);
  };

  document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem(storage.theme) || 'light';
    const locale = localStorage.getItem(storage.locale) || 'vi';
    setTheme(theme);
    translate(locale);

    const selector = document.querySelector('[data-language-select]');
    if (selector) {
      selector.value = locale;
      selector.addEventListener('change', () => setLocale(selector.value));
    }

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
      setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
    });
  });
})();
