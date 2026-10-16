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
      'finance.personalFinance': 'TÀI CHÍNH CÁ NHÂN',
      'finance.transactionCount': 'giao dịch · mới nhất trước',
      'finance.noTransactions': 'Chưa có giao dịch.',
      'finance.transactionPage': 'Trang giao dịch',
      'finance.monthPrefix': 'Tháng',
      'finance.budgetDescription': 'Chỉ cần nhập hạn mức cho các mục bạn muốn theo dõi.',
      'finance.spent': 'Đã chi',
      'finance.remaining': 'Còn lại',
      'finance.budgetLimit': 'Hạn mức (₫)',
      'finance.noBudget': 'Chưa đặt',
      'finance.categoryDescription': 'Sắp xếp giao dịch theo nhóm phù hợp với bạn.',
      'finance.incomeCategories': 'Khoản thu',
      'finance.expenseCategories': 'Khoản chi',
      'finance.inCategory': 'Trong',
      'finance.otherCategory': 'danh mục khác',
      'finance.createCategoryTitle': 'Tạo danh mục',
      'finance.categoryName': 'Tên danh mục',
      'finance.type': 'Loại',
      'finance.parentCategory': 'Danh mục cha',
      'finance.rootCategory': 'Danh mục gốc',
      'finance.icon': 'Biểu tượng',
      'finance.create': 'Tạo danh mục',
      'finance.close': 'Đóng',
      'finance.selectMonth': 'Chọn tháng và năm',
      'finance.selectDate': 'Chọn ngày giao dịch',
      'finance.previousYear': 'Năm trước',
      'finance.nextYear': 'Năm sau',
      'finance.previousMonth': 'Tháng trước',
      'finance.nextMonth': 'Tháng sau',
      'finance.weekday.mon': 'T2',
      'finance.weekday.tue': 'T3',
      'finance.weekday.wed': 'T4',
      'finance.weekday.thu': 'T5',
      'finance.weekday.fri': 'T6',
      'finance.weekday.sat': 'T7',
      'finance.weekday.sun': 'CN',
      'finance.quickCreate': 'Tạo nhanh',
      'finance.quickCash': '+ Tiền mặt',
      'finance.quickBank': '+ Ngân hàng',
      'finance.quickEWallet': '+ Ví điện tử',
      'finance.notice.saved': 'Đã thêm giao dịch.',
      'finance.notice.updated': 'Đã cập nhật giao dịch.',
      'finance.notice.invalid': 'Vui lòng kiểm tra thông tin giao dịch.',
      'finance.notice.budgetSaved': 'Đã lưu ngân sách.',
      'finance.notice.budgetCloned': 'Đã sao chép ngân sách tháng trước.',
      'finance.notice.budgetInvalid': 'Ngân sách không hợp lệ.',
      'finance.notice.categorySaved': 'Đã tạo danh mục.',
      'finance.notice.categoryInvalid': 'Vui lòng kiểm tra tên, loại, icon và danh mục cha.',
      'finance.notice.noEmployee': 'Chưa có nhân viên đang hoạt động.',
      'finance.spendingBreakdown': 'CƠ CẤU CHI TIÊU',
      'finance.spendingByCategory': 'Chi tiêu theo danh mục',
      'finance.vsFirstWeek': 'so với tuần đầu của tháng',
      'finance.vsFirstMonth': 'so với tháng đầu của năm',
      'finance.millionVnd': 'triệu VND',
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
      'finance.personalFinance': 'PERSONAL FINANCE',
      'finance.transactionCount': 'transactions · newest first',
      'finance.noTransactions': 'No transactions yet.',
      'finance.transactionPage': 'Transaction pages',
      'finance.monthPrefix': 'Month',
      'finance.budgetDescription': 'Set limits only for the categories you want to track.',
      'finance.spent': 'Spent',
      'finance.remaining': 'Remaining',
      'finance.budgetLimit': 'Limit (₫)',
      'finance.noBudget': 'Not set',
      'finance.categoryDescription': 'Organize transactions into groups that work for you.',
      'finance.incomeCategories': 'Income categories',
      'finance.expenseCategories': 'Expense categories',
      'finance.inCategory': 'In',
      'finance.otherCategory': 'another category',
      'finance.createCategoryTitle': 'Create category',
      'finance.categoryName': 'Category name',
      'finance.type': 'Type',
      'finance.parentCategory': 'Parent category',
      'finance.rootCategory': 'Root category',
      'finance.icon': 'Icon',
      'finance.create': 'Create category',
      'finance.close': 'Close',
      'finance.selectMonth': 'Select month and year',
      'finance.selectDate': 'Select transaction date',
      'finance.previousYear': 'Previous year',
      'finance.nextYear': 'Next year',
      'finance.previousMonth': 'Previous month',
      'finance.nextMonth': 'Next month',
      'finance.weekday.mon': 'Mon',
      'finance.weekday.tue': 'Tue',
      'finance.weekday.wed': 'Wed',
      'finance.weekday.thu': 'Thu',
      'finance.weekday.fri': 'Fri',
      'finance.weekday.sat': 'Sat',
      'finance.weekday.sun': 'Sun',
      'finance.quickCreate': 'Quick create',
      'finance.quickCash': '+ Cash',
      'finance.quickBank': '+ Bank',
      'finance.quickEWallet': '+ E-wallet',
      'finance.notice.saved': 'Transaction added.',
      'finance.notice.updated': 'Transaction updated.',
      'finance.notice.invalid': 'Check the transaction details and try again.',
      'finance.notice.budgetSaved': 'Budget saved.',
      'finance.notice.budgetCloned': 'Previous month budget copied.',
      'finance.notice.budgetInvalid': 'Budget details are invalid.',
      'finance.notice.categorySaved': 'Category created.',
      'finance.notice.categoryInvalid': 'Check the category name, type, icon, and parent category.',
      'finance.notice.noEmployee': 'No active employee is available.',
      'finance.spendingBreakdown': 'SPENDING BREAKDOWN',
      'finance.spendingByCategory': 'Spending by category',
      'finance.vsFirstWeek': 'vs the first week of the month',
      'finance.vsFirstMonth': 'vs the first month of the year',
      'finance.millionVnd': 'million VND',
    },
  };

  const root = document.documentElement;
  const translate = (locale) => {
    const dictionary = messages[locale] || messages.en;
    document.querySelectorAll('[data-i18n]').forEach((element) => {
      const message = dictionary[element.dataset.i18n];
      if (message) element.textContent = message;
    });
    document.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
      const message = dictionary[element.dataset.i18nAriaLabel];
      if (message) element.setAttribute('aria-label', message);
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
      const message = dictionary[element.dataset.i18nPlaceholder];
      if (message) element.setAttribute('placeholder', message);
    });
    document.querySelectorAll('[data-i18n-label]').forEach((element) => {
      const message = dictionary[element.dataset.i18nLabel];
      if (message) element.setAttribute('label', message);
    });
    root.lang = locale;
    window.MyErpI18n = { locale, t: (key) => dictionary[key] || key };
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
    const locale = localStorage.getItem(storage.locale) || 'en';
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
