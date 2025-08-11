const translations = {
  en: {
    'nav.home': 'Team Management',
    'nav.members': 'Members',
    'nav.projects': 'Projects',
    'nav.tasks': 'Tasks',
    'nav.workload': 'Workload',
    'nav.account': 'Account',
    'welcome': 'Welcome',
    'logout': 'Logout',
    'login.title': 'Manager Login',
    'login.username': 'Username',
    'login.password': 'Password',
    'login.button': 'Login',
    'account.title': 'Account Settings',
    'account.change_password': 'Change Password',
    'account.current_password': 'Current Password',
    'account.new_password': 'New Password',
    'account.confirm_password': 'Confirm New Password',
    'account.change_password_btn': 'Change Password',
    'account.add_manager': 'Add Manager',
    'account.username': 'Username',
    'account.password': 'Password',
    'account.add_manager_btn': 'Add Manager',
    'theme.dark': 'Dark',
    'theme.light': 'Light'
  },
  zh: {
    'nav.home': '团队管理',
    'nav.members': '成员',
    'nav.projects': '项目',
    'nav.tasks': '任务',
    'nav.workload': '工作量',
    'nav.account': '账户',
    'welcome': '欢迎',
    'logout': '退出登录',
    'login.title': '管理员登录',
    'login.username': '用户名',
    'login.password': '密码',
    'login.button': '登录',
    'account.title': '账户设置',
    'account.change_password': '修改密码',
    'account.current_password': '当前密码',
    'account.new_password': '新密码',
    'account.confirm_password': '确认新密码',
    'account.change_password_btn': '修改密码',
    'account.add_manager': '添加管理员',
    'account.username': '用户名',
    'account.password': '密码',
    'account.add_manager_btn': '添加管理员',
    'theme.dark': '暗色',
    'theme.light': '亮色'
  }
};

function doubleConfirm(message) {
  return confirm(message) && confirm('Please confirm again to proceed.');
}

function applyTranslations() {
  const lang = localStorage.getItem('lang') || 'en';
  document.documentElement.lang = lang;
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    const text = translations[lang][key];
    if(text) {
      el.textContent = text;
    }
  });
  const langToggle = document.getElementById('langToggle');
  if(langToggle) {
    langToggle.textContent = lang === 'en' ? '中文' : 'English';
  }
  const themeToggle = document.getElementById('themeToggle');
  if(themeToggle) {
    const theme = localStorage.getItem('theme') || 'light';
    themeToggle.textContent = translations[lang][theme === 'light' ? 'theme.dark' : 'theme.light'];
  }
}

function applyTheme() {
  const theme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-bs-theme', theme);
}

document.addEventListener('DOMContentLoaded', () => {
  applyTheme();
  applyTranslations();

  document.getElementById('langToggle')?.addEventListener('click', () => {
    const current = localStorage.getItem('lang') || 'en';
    const next = current === 'en' ? 'zh' : 'en';
    localStorage.setItem('lang', next);
    applyTranslations();
  });

  document.getElementById('themeToggle')?.addEventListener('click', () => {
    const current = localStorage.getItem('theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', next);
    applyTheme();
    applyTranslations();
  });
});
