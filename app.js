const translations = {
  en: {
    'nav.home': 'Team Management',
    'nav.members': 'Members',
    'nav.projects': 'Projects',
    'nav.directions': 'Research',
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
    'directions.title': 'Research Directions',
    'directions.add': 'Add Direction',
    'directions.table_title': 'Title',
    'directions.table_members': 'Members',
    'directions.table_actions': 'Actions',
    'directions.action_edit': 'Edit',
    'directions.action_members': 'Members',
    'directions.action_delete': 'Delete',
    'directions.none': 'None',
    'projects.title': 'Projects',
    'projects.add': 'Add Project',
    'projects.filter_all': 'All Status',
    'projects.filter.todo': 'Todo',
    'projects.filter.ongoing': 'Ongoing',
    'projects.filter.paused': 'Paused',
    'projects.filter.finished': 'Finished',
    'projects.filter.button': 'Filter',
    'projects.table_title': 'Title',
    'projects.table_members': 'Members',
    'projects.table_begin': 'Begin',
    'projects.table_end': 'End',
    'projects.table_status': 'Status',
    'projects.table_actions': 'Actions',
    'projects.action_edit': 'Edit',
    'projects.action_members': 'Members',
    'projects.action_delete': 'Delete',
    'index.title': 'Dashboard',
    'index.info': 'Use the navigation bar to manage team members, projects, tasks, and workload reports.',
    'theme.dark': 'Dark',
    'theme.light': 'Light'
  },
  zh: {
    'nav.home': '团队管理',
    'nav.members': '成员列表',
    'nav.projects': '横纵项目',
    'nav.directions': '研究方向',
    'nav.tasks': '任务指派',
    'nav.workload': '工作量统计',
    'nav.account': '管理账户',
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
    'directions.title': '研究方向',
    'directions.add': '添加研究方向',
    'directions.table_title': '标题',
    'directions.table_members': '成员',
    'directions.table_actions': '操作',
    'directions.action_edit': '编辑',
    'directions.action_members': '成员',
    'directions.action_delete': '删除',
    'directions.none': '无',
    'projects.title': '项目',
    'projects.add': '添加项目',
    'projects.filter_all': '所有状态',
    'projects.filter.todo': '待办',
    'projects.filter.ongoing': '进行中',
    'projects.filter.paused': '暂停',
    'projects.filter.finished': '已完成',
    'projects.filter.button': '筛选',
    'projects.table_title': '标题',
    'projects.table_members': '成员',
    'projects.table_begin': '开始',
    'projects.table_end': '结束',
    'projects.table_status': '状态',
    'projects.table_actions': '操作',
    'projects.action_edit': '编辑',
    'projects.action_members': '成员',
    'projects.action_delete': '删除',
    'index.title': '仪表板',
    'index.info': '使用导航栏来管理团队成员、项目、任务和工作量报告。',
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

  document.querySelectorAll('.qr-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const url = btn.dataset.url;
      const fullUrl = new URL(url, window.location.href).href;
      const img = document.getElementById('qrImage');
      if (img) {
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(fullUrl);
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();
      }
    });
  });
});
