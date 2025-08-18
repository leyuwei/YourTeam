const translations = {
  en: {
    'nav.home': 'Team Management',
    'nav.members': 'Members',
    'nav.todolist': 'Todolist',
    'nav.projects': 'Projects',
    'nav.directions': 'Research',
    'nav.tasks': 'Tasks',
    'nav.workload': 'Workload',
    'nav.account': 'Account',
    'welcome': 'Welcome',
    'logout': 'Logout',
    'header.title': 'Team Management Platform',
    'qr.scan': 'Scan to Enter',
    'qr.copy': 'Copy Link',
    'login.title': 'Manager Login',
    'login.title.manager': 'Manager Login',
    'login.title.member': 'Member Login',
    'login.username': 'Username',
    'login.password': 'Password',
    'login.button': 'Login',
    'login.radio.manager': 'Manager',
    'login.radio.member': 'Member',
    'login.name': 'Name',
    'login.identity': 'Identity Number',
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
    'directions.toggle_details': 'Show Member Details',
    'directions.assignment_title': 'Direction Assignments',
    'directions.assignment_member': 'Member',
    'directions.assignment_direction': 'Research Directions',
    'directions.none': 'None',
    'direction_edit.title_edit': 'Edit Direction',
    'direction_edit.title_add': 'Add Direction',
    'direction_edit.label_title': 'Direction Title',
    'direction_edit.label_description': 'Description',
    'direction_edit.label_bg': 'Background Color',
    'direction_edit.save': 'Save',
    'direction_edit.cancel': 'Cancel',
    'project_edit.title_edit': 'Edit Project',
    'project_edit.title_add': 'Add Project',
    'project_edit.error_range': 'End date must be after begin date',
    'project_edit.label_title': 'Project Title',
    'project_edit.label_description': 'Project Description',
    'project_edit.label_bg': 'Background Color',
    'project_edit.label_begin': 'Begin Date',
    'project_edit.label_end': 'End Date',
    'project_edit.label_status': 'Status',
    'project_edit.save': 'Save',
    'project_edit.cancel': 'Cancel',
    'member_edit.title_edit': 'Edit Member',
    'member_edit.title_add': 'Add Member',
    'member_edit.save': 'Save',
    'member_edit.cancel': 'Cancel',
    'task_edit.title_edit': 'Edit Task',
    'task_edit.title_add': 'Add Task',
    'task_edit.label_description': 'Description',
    'task_edit.label_start': 'Start Date',
    'task_edit.label_status': 'Status',
    'task_edit.save': 'Save',
    'task_edit.cancel': 'Cancel',
    'direction_members.title_prefix': 'Direction Members -',
    'direction_members.remove': 'Remove',
    'direction_members.add_member': 'Add Member',
    'direction_members.label_member': 'Member',
    'direction_members.select_member': 'Select Member',
    'direction_members.save': 'Add',
    'direction_members.back': 'Back',
    'project_members.title_prefix': 'Project Members -',
    'project_members.current_members': 'Current Members',
    'project_members.join_date': 'Join Date',
    'project_members.remove': 'Remove',
    'project_members.add_member': 'Add Member',
    'project_members.label_member': 'Member',
    'project_members.select_member': 'Select Member',
    'project_members.label_join': 'Join Date',
    'project_members.save': 'Add',
    'project_members.back': 'Back',
    'project_members.history_title': 'Member History',
    'project_members.history_member': 'Member',
    'project_members.history_join': 'Join Date',
    'project_members.history_exit': 'Exit Date',
    'members_import.title': 'Import Members from Excel (CSV)',
    'members_import.import': 'Import',
    'members_import.cancel': 'Cancel',
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
    'projects.toggle_details': 'Show Member Details',
    'projects.participation_title': 'Project Participation',
    'projects.participation.member': 'Member',
    'projects.participation.projects': 'Projects',
    'projects.status.todo': 'Todo',
    'projects.status.ongoing': 'Ongoing',
    'projects.status.paused': 'Paused',
    'projects.status.finished': 'Finished',
    'projects.no_direction': 'No direction',
    'index.title': 'Dashboard',
    'index.info': 'Use the navigation bar to manage team members, projects, tasks, and workload reports.',
    'theme.dark': 'Dark',
    'theme.light': 'Light',
    'bold_font': 'Bold font',
    'members.title': 'Team Members',
    'members.add': 'Add Member',
    'members.import': 'Import from Spreadsheet',
    'members.export': 'Export to Spreadsheet',
    'members.request_update': 'Request Info Update',
    'members.filter.all': 'All',
    'members.filter.in_work': 'In Work',
    'members.filter.exited': 'Exited',
    'members.table.campus_id': 'Campus ID',
    'members.table.name': 'Name',
    'members.table.status': 'Status',
    'members.table.email': 'Email',
    'members.table.identity_number': 'Identity Number',
    'members.table.year_of_join': 'Year of Join',
    'members.table.current_degree': 'Current Degree',
    'members.table.degree_pursuing': 'Degree Pursuing',
    'members.table.phone': 'Phone',
    'members.table.wechat': 'WeChat',
    'members.table.department': 'Department',
    'members.table.workplace': 'Workplace',
    'members.table.homeplace': 'Homeplace',
    'members.table.actions': 'Actions',
    'members.action.edit': 'Edit',
    'members.action.remove': 'Remove',
    'members.status.in_work': 'In Work',
    'members.status.exited': 'Exited',
    'members.confirm.remove': 'Are you sure to remove this member? This action requires caution!',
    'todolist.title': 'Todolist',
    'todolist.switch_week': 'Switch Week',
    'todolist.export': 'Export',
    'todolist.print': 'Print',
    'todolist.copy_next': 'Continue to Next Week',
    'todolist.category.work': 'Work',
    'todolist.category.personal': 'Personal',
    'todolist.category.longterm': 'Long Term',
    'todolist.days.mon': 'Mon',
    'todolist.days.tue': 'Tue',
    'todolist.days.wed': 'Wed',
    'todolist.days.thu': 'Thu',
    'todolist.days.fri': 'Fri',
    'todolist.days.sat': 'Sat',
    'todolist.days.sun': 'Sun',
    'tasks.title': 'Tasks Assignment',
    'tasks.add': 'New Task',
    'tasks.filter_all': 'All Status',
    'tasks.filter.active': 'Active',
    'tasks.filter.paused': 'Paused',
    'tasks.filter.finished': 'Finished',
    'tasks.filter.button': 'Filter',
    'tasks.table_title': 'Title',
    'tasks.table_start': 'Start',
    'tasks.table_status': 'Status',
    'tasks.table_actions': 'Actions',
    'tasks.action_edit': 'Edit',
    'tasks.action_affairs': 'Affairs',
    'tasks.action_fill': 'Self Fill',
    'tasks.action_delete': 'Delete',
    'tasks.status.active': 'Active',
    'tasks.status.paused': 'Paused',
    'tasks.status.finished': 'Finished',
    'tasks.confirm.delete': 'Delete task?',
    'task_affairs.title_prefix': 'Task Affairs - ',
    'task_affairs.table_description': 'Description',
    'task_affairs.table_members': 'Members',
    'task_affairs.table_start': 'Start Date',
    'task_affairs.table_end': 'End Date',
    'task_affairs.table_days': 'Days',
    'task_affairs.table_actions': 'Actions',
    'task_affairs.action_edit': 'Edit',
    'task_affairs.action_delete': 'Delete',
    'task_affairs.edit_title': 'Edit Affair',
    'task_affairs.label_description': 'Description',
    'task_affairs.label_start': 'Start Date',
    'task_affairs.label_end': 'End Date',
    'task_affairs.save': 'Save',
    'task_affairs.cancel': 'Cancel',
    'task_affairs.new_title': 'New Affair',
    'task_affairs.label_members': 'Members (hold Ctrl to select multiple)',
    'task_affairs.add': 'Add Affair',
    'task_affairs.back': 'Back',
    'task_affairs.error.range': 'End date must not be earlier than start date',
    'task_affairs.workload_prefix': 'Workload: ',
    'task_affairs.workload_suffix': ' days',
    'task_affairs.confirm.delete': 'Delete affair?',
    'task_affairs.merge_selected': 'Merge Selected',
    'task_affairs.confirm.merge': 'Merge selected affairs?',
    'workload.title': 'Workload Report',
    'workload.error.range': 'End date must be after start date',
    'workload.label.start': 'Start Date',
    'workload.label.end': 'End Date',
    'workload.generate': 'Generate',
    'workload.export': 'Export to EXCEL',
    'workload.table.rank': 'Rank',
    'workload.table.campus_id': 'Campus ID',
    'workload.table.name': 'Name',
    'workload.table.task_detail': 'Task Detail',
    'workload.table.task_hours': 'Task Hours',
    'account.msg.password_mismatch': 'New passwords do not match',
    'account.msg.password_updated': 'Password updated successfully',
    'account.msg.current_incorrect': 'Current password is incorrect',
    'account.msg.manager_added': 'Manager added',
    'account.msg.manager_add_error': 'Error adding manager'
  },
  zh: {
    'nav.home': '团队管理',
    'nav.members': '成员列表',
    'nav.todolist': '待办事项',
    'nav.projects': '横纵项目',
    'nav.directions': '研究方向',
    'nav.tasks': '任务指派',
    'nav.workload': '工作量统计',
    'nav.account': '管理账户',
    'welcome': '欢迎',
    'logout': '退出登录',
    'header.title': '团队管理平台',
    'qr.scan': '扫码进入',
    'qr.copy': '复制链接',
    'login.title': '管理员登录',
    'login.title.manager': '管理员登录',
    'login.title.member': '成员登录',
    'login.username': '用户名',
    'login.password': '密码',
    'login.button': '登录',
    'login.radio.manager': '管理员',
    'login.radio.member': '一般成员',
    'login.name': '姓名',
    'login.identity': '身份证号',
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
    'directions.toggle_details': '显示成员详情',
    'directions.assignment_title': '研究方向指派情况',
    'directions.assignment_member': '成员',
    'directions.assignment_direction': '研究方向',
    'directions.none': '无',
    'direction_edit.title_edit': '编辑研究方向',
    'direction_edit.title_add': '添加研究方向',
    'direction_edit.label_title': '方向题目',
    'direction_edit.label_description': '方向具体描述',
    'direction_edit.label_bg': '背景颜色',
    'direction_edit.save': '保存',
    'direction_edit.cancel': '取消',
    'project_edit.title_edit': '编辑项目',
    'project_edit.title_add': '添加项目',
    'project_edit.error_range': '结项时间必须晚于立项时间',
    'project_edit.label_title': '项目标题',
    'project_edit.label_description': '项目描述',
    'project_edit.label_bg': '背景颜色',
    'project_edit.label_begin': '立项时间',
    'project_edit.label_end': '结项时间',
    'project_edit.label_status': '状态',
    'project_edit.save': '保存',
    'project_edit.cancel': '取消',
    'member_edit.title_edit': '编辑成员',
    'member_edit.title_add': '新增成员',
    'member_edit.save': '保存',
    'member_edit.cancel': '取消',
    'task_edit.title_edit': '编辑任务',
    'task_edit.title_add': '新建任务',
    'task_edit.label_description': '任务描述',
    'task_edit.label_start': '起始时间',
    'task_edit.label_status': '状态',
    'task_edit.save': '保存',
    'task_edit.cancel': '取消',
    'direction_members.title_prefix': '研究方向成员 -',
    'direction_members.remove': '删除',
    'direction_members.add_member': '新增成员',
    'direction_members.label_member': '成员',
    'direction_members.select_member': '选择成员',
    'direction_members.save': '新增',
    'direction_members.back': '返回',
    'project_members.title_prefix': '项目成员 -',
    'project_members.current_members': '当前成员',
    'project_members.join_date': '入项日期',
    'project_members.remove': '移除',
    'project_members.add_member': '新增成员',
    'project_members.label_member': '成员',
    'project_members.select_member': '选择成员',
    'project_members.label_join': '入项日期',
    'project_members.save': '新增',
    'project_members.back': '返回',
    'project_members.history_title': '成员变动历史',
    'project_members.history_member': '成员',
    'project_members.history_join': '入项日期',
    'project_members.history_exit': '退出日期',
    'members_import.title': '从 Excel (CSV) 导入成员',
    'members_import.import': '导入',
    'members_import.cancel': '取消',
    'projects.title': '横纵项目',
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
    'projects.toggle_details': '显示成员详情',
    'projects.participation_title': '项目参与人员情况',
    'projects.participation.member': '成员',
    'projects.participation.projects': '参与项目',
    'projects.status.todo': '待办',
    'projects.status.ongoing': '进行中',
    'projects.status.paused': '暂停',
    'projects.status.finished': '已完成',
    'projects.no_direction': '无研究方向',
    'index.title': '仪表板',
    'index.info': '使用导航栏来管理团队成员、项目、任务和工作量报告。',
    'theme.dark': '暗色',
    'theme.light': '亮色',
    'bold_font': '加粗字体',
    'members.title': '团队成员',
    'members.add': '新增成员',
    'members.import': '从表格导入',
    'members.export': '导出至表格',
    'members.request_update': '请求信息更新',
    'members.filter.all': '全部',
    'members.filter.in_work': '在岗',
    'members.filter.exited': '已离退',
    'members.table.campus_id': '一卡通号',
    'members.table.name': '姓名',
    'members.table.status': '状态',
    'members.table.email': '正式邮箱',
    'members.table.identity_number': '身份证号',
    'members.table.year_of_join': '入学年份',
    'members.table.current_degree': '已获学位',
    'members.table.degree_pursuing': '当前学历',
    'members.table.phone': '手机号',
    'members.table.wechat': '微信号',
    'members.table.department': '所处学院/单位',
    'members.table.workplace': '工作地点',
    'members.table.homeplace': '家庭住址',
    'members.table.actions': '操作',
    'members.action.edit': '编辑',
    'members.action.remove': '移除',
    'members.status.in_work': '在岗',
    'members.status.exited': '已离退',
    'members.confirm.remove': '确认要移除该成员吗? 此操作需万分谨慎！',
    'todolist.title': '待办事项',
    'todolist.switch_week': '切换周',
    'todolist.export': '导出',
    'todolist.print': '打印',
    'todolist.copy_next': '复制到下周',
    'todolist.category.work': '工作',
    'todolist.category.personal': '私人',
    'todolist.category.longterm': '长期',
    'todolist.days.mon': '周一',
    'todolist.days.tue': '周二',
    'todolist.days.wed': '周三',
    'todolist.days.thu': '周四',
    'todolist.days.fri': '周五',
    'todolist.days.sat': '周六',
    'todolist.days.sun': '周日',
    'tasks.title': '任务指派',
    'tasks.add': '新建任务',
    'tasks.filter_all': '所有状态',
    'tasks.filter.active': '进行中',
    'tasks.filter.paused': '暂停',
    'tasks.filter.finished': '已结束',
    'tasks.filter.button': '筛选',
    'tasks.table_title': '任务标题',
    'tasks.table_start': '开始日期',
    'tasks.table_status': '状态',
    'tasks.table_actions': '操作',
    'tasks.action_edit': '编辑信息',
    'tasks.action_affairs': '下辖具体事务',
    'tasks.action_fill': '请成员自己填',
    'tasks.action_delete': '删除',
    'tasks.status.active': '进行中',
    'tasks.status.paused': '暂停',
    'tasks.status.finished': '已结束',
    'tasks.confirm.delete': '删除任务？',
    'task_affairs.title_prefix': '下辖具体事务 - ',
    'task_affairs.table_description': '具体事务描述',
    'task_affairs.table_members': '负责成员',
    'task_affairs.table_start': '起始日期',
    'task_affairs.table_end': '结束日期',
    'task_affairs.table_days': '天数',
    'task_affairs.table_actions': '操作',
    'task_affairs.action_edit': '编辑',
    'task_affairs.action_delete': '删除',
    'task_affairs.edit_title': '编辑事务',
    'task_affairs.label_description': '具体事务描述',
    'task_affairs.label_start': '起始日期',
    'task_affairs.label_end': '结束日期',
    'task_affairs.save': '保存',
    'task_affairs.cancel': '取消',
    'task_affairs.new_title': '新建具体事务',
    'task_affairs.label_members': '负责成员 (按住Ctrl键点选多个人)',
    'task_affairs.add': '新增事务',
    'task_affairs.back': '返回',
    'task_affairs.error.range': '结束日期必须不早于起始日期',
    'task_affairs.workload_prefix': '本次事务工作量：',
    'task_affairs.workload_suffix': ' 天',
    'task_affairs.confirm.delete': '删除事务?',
    'task_affairs.merge_selected': '合并选择的事务',
    'task_affairs.confirm.merge': '合并已选事务？',
    'workload.title': '工作量统计报表',
    'workload.error.range': '报表截止时间必须晚于起始时间',
    'workload.label.start': '报表起始时间',
    'workload.label.end': '报表截止时间',
    'workload.generate': '生成报表',
    'workload.export': '导出为EXCEL',
    'workload.table.rank': '排名',
    'workload.table.campus_id': '一卡通号',
    'workload.table.name': '姓名',
    'workload.table.task_detail': '具体任务',
    'workload.table.task_hours': '任务投入时长',
    'account.msg.password_mismatch': '两次新密码不一致',
    'account.msg.password_updated': '密码更新成功',
    'account.msg.current_incorrect': '当前密码错误',
    'account.msg.manager_added': '管理员已添加',
    'account.msg.manager_add_error': '添加管理员出错'
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
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    const key = el.getAttribute('data-i18n-title');
    const text = translations[lang][key];
    if(text) {
      el.setAttribute('title', text);
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
  applyTeamName?.();
}

function applyTheme() {
  const theme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-bs-theme', theme);
}

function initApp() {
  applyTheme();
  applyTranslations();

  const langBtn = document.getElementById('langToggle');
  if (langBtn) {
    langBtn.addEventListener('click', () => {
      const current = localStorage.getItem('lang') || 'en';
      const next = current === 'en' ? 'zh' : 'en';
      localStorage.setItem('lang', next);
      applyTranslations();
    });
  }

  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      const current = localStorage.getItem('theme') || 'light';
      const next = current === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', next);
      applyTheme();
      applyTranslations();
    });
  }

  document.querySelectorAll('.qr-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const url = btn.dataset.url;
      const fullUrl = new URL(url, window.location.href).href;
      const img = document.getElementById('qrImage');
      if (img) {
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(fullUrl);
      }
      const linkInput = document.getElementById('qrLinkInput');
      if (linkInput) {
        linkInput.value = fullUrl;
      }
      const copyBtn = document.getElementById('qrCopyBtn');
      if (copyBtn) {
        copyBtn.onclick = () => navigator.clipboard.writeText(fullUrl);
      }
      const modal = new bootstrap.Modal(document.getElementById('qrModal'));
      modal.show();
    });
  });

  const nav = document.querySelector('.navbar-nav');
  if(nav){
    const indicator = document.createElement('span');
    indicator.className = 'nav-indicator';
    nav.appendChild(indicator);
    const links = Array.from(nav.querySelectorAll('.nav-link'));
    function moveIndicator(el){
      if(!el) return;
      const rect = el.getBoundingClientRect();
      const navRect = nav.getBoundingClientRect();
      indicator.style.width = rect.width + 'px';
      indicator.style.transform = `translateX(${rect.left - navRect.left}px)`;
    }
    const active = nav.querySelector('.nav-link.active');
    const activeIdx = links.indexOf(active || links[0]);
    const prevIndex = parseInt(sessionStorage.getItem('navActiveIndex'), 10);

    if(!isNaN(prevIndex) && links[prevIndex]){
      // Place indicator at the previous location without animation so it
      // doesn't slide in from the left-most position on initial load.
      const prev = links[prevIndex];
      indicator.style.transition = 'none';
      moveIndicator(prev);
      // Force reflow to ensure the styles above are applied before restoring
      // the transition for the animated move to the current page.
      indicator.offsetWidth; // eslint-disable-line no-unused-expressions
      indicator.style.transition = '';
      requestAnimationFrame(()=>moveIndicator(active || links[0]));
    } else {
      moveIndicator(active || links[0]);
    }

    // Remember the currently active navigation index so the indicator can
    // animate from the previous page's position on the next load. We store
    // the index of the page being left rather than the destination page.
    sessionStorage.setItem('navActiveIndex', activeIdx);
    links.forEach(link => {
      link.addEventListener('click', () => {
        sessionStorage.setItem('navActiveIndex', activeIdx);
      });
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
