const translations = {
  en: {
    'nav.home': 'Team Management',
    'nav.members': 'Members',
    'nav.askme': 'AskMe',
    'nav.todolist': 'Todo',
    'nav.projects': 'Project',
    'nav.directions': 'Research',
    'nav.offices': 'Office',
    'nav.collect': 'Collect',
    'nav.notifications': 'Notify',
    'nav.reimburse': 'Finance',
    'nav.assets': 'Asset',
    'nav.tasks': 'Task',
    'nav.workload': 'Workload',
    'nav.account': 'Account',
    'askme.title': 'AskMe',
    'askme.subtitle': 'Find answers across policies, offices, assets, and the AskMe knowledge base.',
    'askme.manage': 'Manage Knowledge Base',
    'askme.search_label': 'Search',
    'askme.search_placeholder': 'Ask anything…',
    'askme.search_btn': 'Search',
    'askme.searching': 'Searching…',
    'askme.hint': 'Tip: try keywords like "travel", "office", or "device".',
    'askme.no_results': 'No matching knowledge found yet.',
    'askme.show_detail': 'Show full answer',
    'askme.hide_detail': 'Hide full answer',
    'askme.download': 'Download document',
    'askme.office_members': 'People in this office',
    'askme.manage_title': 'Manage AskMe Knowledge',
    'askme.content_label': 'Knowledge Content',
    'askme.content_hint': 'One language is enough here—we will serve it to every user.',
    'askme.keywords_label': 'Keywords (comma separated)',
    'askme.keywords_hint': 'Use multiple candidate keywords to improve fuzzy search.',
    'askme.save': 'Save',
    'askme.cancel': 'Cancel',
    'askme.table.content': 'Content',
    'askme.table.keywords': 'Keywords',
    'askme.table.actions': 'Actions',
    'askme.no_entries': 'No knowledge entries yet.',
    'askme.edit': 'Edit',
    'askme.delete': 'Delete',
    'welcome': 'Welcome',
    'logout': 'Logout',
    'header.title': 'Team Management Platform',
    'qr.scan': 'Scan to Enter',
    'qr.copy': 'Copy Link',
    'login.title': 'Member Login',
    'login.title.manager': 'Manager Login',
    'login.title.member': 'Member Login',
    'login.section.manager.title': 'Administrator Access',
    'login.section.manager.description': 'Sign in with your administrator account to manage teams, projects, notifications and more.',
    'login.section.member.title': 'Member Access',
    'login.section.member.description': 'Use the login method that you configured on your dashboard.',
    'login.switch.label': 'Switch login role',
    'login.switch.member': 'Member Login',
    'login.switch.manager': 'Administrator Login',
    'login.username': 'Username',
    'login.password': 'Password',
    'login.button': 'Login',
    'login.button.manager': 'Manager Login',
    'login.button.member': 'Member Login',
    'login.radio.manager': 'Manager',
    'login.radio.member': 'Member',
    'login.warning.manager': 'You are logging in as a manager.',
    'login.warning.member': 'You are logging in as a normal member.',
    'login.name': 'Name',
    'login.identity': 'Identity Number',
    'login.member.mode.title': 'Login Method',
    'login.member.mode.identity': 'Identity Number',
    'login.member.mode.password': 'Password',
    'login.member.mode.identity_hint': 'Verify with the identity number stored in your profile.',
    'login.member.mode.password_hint': 'Verify with the password you set on the dashboard.',
    'login.placeholder.username': 'Username',
    'login.placeholder.password': 'Password',
    'login.placeholder.name': 'Name',
    'login.placeholder.identity': 'Identity Number',
    'login.error.manager_invalid': 'Invalid username or password.',
    'login.error.member_name_required': 'Please enter your name.',
    'login.error.member_not_found': 'Account not found. Please confirm your login method.',
    'login.error.member_identity_required': 'Please enter your identity number.',
    'login.error.member_identity_invalid': 'Identity number verification failed.',
    'login.error.member_password_required': 'Please enter your password.',
    'login.error.member_password_invalid': 'Password verification failed.',
    'login.error.member_mode_mismatch': 'This account uses a different login method. Please adjust your selection.',
    'member.login_settings.title': 'Login Preferences',
    'member.login_settings.description': 'Choose the credential that you would like to use the next time you sign in.',
    'member.login_settings.current_label': 'Current login method:',
    'member.login_settings.manage_button': 'Manage Login Method',
    'member.login_settings.method_label': 'Preferred login method',
    'member.login_settings.method.identity': 'Identity Number',
    'member.login_settings.method.password': 'Password',
    'member.login_settings.method.identity_hint': 'Log in with the identity number saved in your profile.',
    'member.login_settings.method.password_hint': 'Log in with the password you set below. Identity number login will be disabled.',
    'member.login_settings.password.label': 'New Password',
    'member.login_settings.password.confirm': 'Confirm Password',
    'member.login_settings.password.placeholder': 'Enter new password',
    'member.login_settings.password.confirm_placeholder': 'Confirm password',
    'member.login_settings.submit': 'Save Preference',
    'member.login_settings.success_identity': 'Identity number login has been enabled for your account.',
    'member.login_settings.success_password': 'Password login has been enabled. Use your new password next time.',
    'member.login_settings.error_password_required': 'Please enter and confirm your new password to enable password login.',
    'member.login_settings.error_password_mismatch': 'The passwords you entered do not match.',
    'member.login_settings.error_invalid_method': 'Unsupported login method.',
    'member.login_settings.current.identity': 'Current method: Identity number login',
    'member.login_settings.current.password': 'Current method: Password login',
    'account.title': 'Account Settings',
    'account.hero.title': 'Administrator Center',
    'account.hero.subtitle': 'Keep administrator access organized and secure for your team.',
    'account.change_password': 'Change Password',
    'account.current_password': 'Current Password',
    'account.new_password': 'New Password',
    'account.confirm_password': 'Confirm New Password',
    'account.change_password_btn': 'Change Password',
    'account.section.security_hint': 'Update your password regularly to protect sensitive information.',
    'account.add_manager': 'Add Manager',
    'account.section.add_hint': 'Create an additional administrator account for collaborators.',
    'account.username': 'Username',
    'account.password': 'Password',
    'account.add_manager_btn': 'Add Manager',
    'account.section.list_title': 'Administrator Directory',
    'account.section.list_hint': 'Review who can access the management console. Remove accounts that are no longer needed.',
    'account.manager_id_label': 'ID',
    'account.manager_list_empty': 'No administrators yet',
    'account.badge.you': 'You',
    'account.delete': 'Delete',
    'account.delete_disabled': 'You cannot delete your own account',
    'account.delete_confirm': 'Delete this administrator? This action cannot be undone.',
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
    'project_edit.error_generic': 'Failed to save project. Please try again.',
    'project_edit.not_found': 'Project not found',
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
    'offices.title': 'Offices',
    'offices.add': 'Add Office',
    'offices.summary.title': 'Seat Overview',
    'offices.summary.total_seats': 'Total Seats',
    'offices.summary.available_seats': 'Unassigned Seats',
    'offices.summary.active_members': 'Active Members',
    'offices.summary.unassigned_members': 'Members Without Seats',
    'offices.table.name': 'Office Name',
    'offices.table.location': 'Location Description',
    'offices.table.region': 'Region',
    'offices.table.seats': 'Seat Count',
    'offices.table.available': 'Remaining Seats',
    'offices.table.members': 'Members in Office',
    'offices.action.view': 'View Layout',
    'offices.action.edit': 'Edit',
    'offices.action.delete': 'Delete',
    'offices.none': 'None',
    'offices.selection.closed': 'Selection Closed',
    'offices.selection.not_whitelisted': 'Whitelist Only',
    'offices.members_overview.title': 'Member Office Assignments',
    'offices.members_overview.member': 'Member',
    'offices.members_overview.year_of_join': 'Year of Join',
    'offices.members_overview.degree': 'Degree Pursuing',
    'offices.members_overview.offices': 'Office & Seats',
    'offices.members_overview.none': 'None',
    'offices.status.available': 'Available',
    'offices.status.occupied': 'Occupied',
    'office_edit.title_add': 'Add Office',
    'office_edit.title_edit': 'Edit Office',
    'office_edit.label_name': 'Office Name',
    'office_edit.label_location': 'Location Description',
    'office_edit.label_region': 'Region',
    'office_edit.label_image': 'Layout Image',
    'office_edit.label_seats': 'Seat Layout',
    'office_edit.label_open_selection': 'Allow seat selection',
    'office_edit.open_selection_hint': 'When disabled, only managers can adjust seat assignments.',
    'office_edit.whitelist.title': 'Seat Selection Whitelist',
    'office_edit.whitelist.description': 'Only selected on-duty members can pick seats in this office.',
    'office_edit.whitelist.search_placeholder': 'Search members…',
    'office_edit.whitelist.select_all': 'Select All',
    'office_edit.whitelist.clear': 'Clear',
    'office_edit.whitelist.empty': 'No active members available.',
    'office_edit.instructions': 'Click the layout to add seats. Drag markers to fine-tune positions.',
    'office_edit.instructions_remove': 'Use the list below to rename or remove seats.',
    'office_edit.table.label': 'Seat Label',
    'office_edit.table.actions': 'Actions',
    'office_edit.remove': 'Remove',
    'office_edit.default_label': 'Seat',
    'office_edit.no_image': 'Please upload a layout image before placing seats.',
    'office_edit.current_image': 'Current layout',
    'office_edit.seats_empty': 'No seats defined yet.',
    'office_edit.save': 'Save',
    'office_edit.cancel': 'Cancel',
    'office_view.title': 'Office Layout',
    'office_view.info.location': 'Location',
    'office_view.info.region': 'Region',
    'office_view.info.total': 'Total Seats',
    'office_view.info.available': 'Remaining Seats',
    'office_view.info.selection': 'Seat Selection',
    'office_view.instructions.member': 'Click an available seat to claim it, or click your seat to release it.',
    'office_view.instructions.manager': 'Choose a member and click a seat to assign it. Select Clear Seat to free a seat.',
    'office_view.instructions.closed': 'Seat selection is currently closed. Please contact an administrator if you need changes.',
    'office_view.instructions.not_allowed': 'You are not on the whitelist for this office.',
    'office_view.select.member': 'Select member',
    'office_view.select.clear': 'Clear Seat',
    'office_view.table.seat': 'Seat',
    'office_view.table.status': 'Status',
    'office_view.table.member': 'Member',
    'office_view.status.available': 'Available',
    'office_view.status.occupied': 'Occupied',
    'office_view.member.empty': 'Unassigned',
    'office_view.message.select_member': 'Please select a member first.',
    'office_view.message.unavailable': 'This seat is already occupied.',
    'office_view.message.no_permission': 'You can only manage your own seats.',
    'office_view.message.error': 'Operation failed, please try again.',
    'office_view.message.closed': 'Seat selection is currently closed.',
    'office_view.message.not_allowed': 'You are not allowed to manage seats in this office.',
    'office_view.selection.open': 'Open',
    'office_view.selection.closed': 'Closed',
    'office_view.selection.not_whitelisted': 'Whitelist Only',
    'project_members.title_prefix': 'Project Members -',
    'project_members.current_members': 'Current Members',
    'project_members.join_date': 'Join Date',
    'project_members.remove': 'Remove',
    'project_members.add_member': 'Add Member',
    'project_members.label_member': 'Member',
    'project_members.select_member': 'Select Member',
    'project_members.label_join': 'Join Date',
    'project_members.label_exit': 'Exit Date',
    'project_members.save': 'Add',
    'project_members.back': 'Back',
    'project_members.history_title': 'Member History',
    'project_members.history_member': 'Member',
    'project_members.history_join': 'Join Date',
    'project_members.history_exit': 'Exit Date',
    'project_members.remove_confirm_title': 'Remove Member',
    'project_members.remove_confirm': 'Please confirm the exit date for the selected member.',
    'project_members.invalid_request': 'Operation failed, please try again.',
    'project_members.missing_project': 'Project not specified.',
    'members_import.title': 'Import Members from CSV',
    'members_import.import': 'Import',
    'members_import.cancel': 'Cancel',
    'members_import.download_template': 'Download template',
    'members_import.back': 'Back to member list',
    'members_import.preview.title': 'Preview import result',
    'members_import.preview.summary.new': 'New records',
    'members_import.preview.summary.update': 'Will update',
    'members_import.preview.summary.skip': 'Skipped',
    'members_import.preview.update_warning': 'Existing members with the same Campus ID will be updated. Please review carefully.',
    'members_import.preview.column.campus_id': 'Campus ID',
    'members_import.preview.column.name': 'Name',
    'members_import.preview.column.status': 'Result',
    'members_import.preview.column.issues': 'Issues',
    'members_import.preview.action.create': 'New',
    'members_import.preview.action.update': 'Update',
    'members_import.preview.action.skip': 'Skip',
    'members_import.preview.ack_updates': 'I understand that existing records will be updated.',
    'members_import.preview.confirm': 'Confirm import',
    'members_import.preview.restart': 'Upload another file',
    'members_import.upload.title': 'Upload CSV file',
    'members_import.upload.hint': 'Please use the template to prepare data. Only CSV format is supported.',
    'members_import.upload.label': 'Select CSV file',
    'members_import.upload.preview': 'Preview import',
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
    'index.notifications': 'Notifications',
    'index.pending_notifications.title': 'Pending Notifications',
    'index.pending_notifications.description': 'You still have notifications that need your attention.',
    'index.pending_notifications.maybe_later': 'Maybe Later',
    'index.regulations': 'Regulations',
    'theme.dark': 'Dark',
    'theme.light': 'Light',
    'bold_font': 'Bold font',
    'members.title': 'Team Members',
    'members.add': 'Add Member',
  'members.import': 'Import from Spreadsheet',
  'members.export': 'Export to Spreadsheet',
  'members.request_update': 'Request Info Update',
  'members.toggle_color': 'Toggle Colors',
  'members.extra.edit': 'Edit Attributes',
  'members.extra.modal_title': 'Edit Extra Attributes',
  'members.extra.description': 'Extra attributes will appear in the member list and in all member forms.',
  'members.extra.add': 'Add Attribute',
  'members.extra.cancel': 'Cancel',
  'members.extra.save': 'Save Changes',
  'members.extra.empty': 'No extra attributes defined yet.',
  'members.extra.validation': 'Please provide either a Chinese or an English name for each attribute.',
  'members.extra.save_error': 'Failed to save attributes. Please try again later.',
  'members.extra.section_title': 'Extra Attributes',
  'members.extra.field.name_zh': 'Chinese Name',
  'members.extra.field.name_en': 'English Name',
  'members.extra.field.type': 'Attribute Type',
  'members.extra.field.default_value': 'Default Value',
  'members.extra.type.text': 'Text',
  'members.extra.type.media': 'Media (image, archive, etc.)',
  'members.extra.helper.media_input': 'Upload a file (image, archive, etc.). No default value is required.',
  'members.extra.current_file': 'Current file',
  'members.extra.no_file': 'No file uploaded',
  'members.extra.selected_file': 'Selected file',
  'members.extra.media.preview_image': 'Preview image',
  'members.extra.media.download_file': 'Open or download file',
  'members.extra.clear_file': 'Clear file',
  'members.extra.will_clear': 'File will be removed when saved',
  'members.extra.delete': 'Remove',
  'members.summary.title': 'Summary',
  'members.summary.in_work_total': 'Current Active Members',
  'members.summary.by_degree': 'Active Members by Current Degree',
  'members.summary.degree.unknown': 'Unspecified',
  'members.summary.none': 'No active members currently.',
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
    'todolist.prev_week': 'Previous Week',
    'todolist.next_week': 'Next Week',
    'todolist.cut_tomorrow': 'Cut to Tomorrow',
    'todolist.drag_handle': 'Drag to reorder',
    'todolist.assessment': 'Assessment',
    'todolist.assessment.generate': 'Generate',
    'todolist.assessment.no_items': 'No todo items',
    'todolist.assessment.export_txt': 'Export TXT',
    'todolist.assessment.exporting': 'Exporting…',
    'todolist.assessment.export_error': 'Export failed, please try again.',
    'todolist.assessment.export_missing_range': 'Please select both start and end dates before exporting.',
    'todolist.assessment.prompts.title': 'AI Prompt Suggestions',
    'todolist.assessment.prompts.helper_badge': 'AI Assistant',
    'todolist.assessment.prompts.open': 'AI Prompts',
    'todolist.assessment.prompts.close': 'Close',
    'todolist.assessment.prompts.description': 'Copy one of the prompts below and ask your AI assistant to summarise key accomplishments between {start} and {end} for each category, merging differently worded entries that refer to the same work.',
    'todolist.assessment.prompts.item1': 'Act as a professional weekly report assistant. Based on my todo records between {start} and {end}, list the most impactful highlights under "Work", "Personal", and "Long Term". Be mindful that similar wording may describe the same task—merge them into consolidated bullet points.',
    'todolist.assessment.prompts.item2': 'Review my todos from {start} to {end} and create a retrospective. Summarise the important achievements in the "Work", "Personal", and "Long Term" categories, connecting items that are essentially the same activity even if phrased differently. Present each insight as a clear bullet.',
    'todolist.assessment.prompts.item3': 'From the todo entries recorded between {start} and {end}, identify the representative actions for each of the three categories. Group together descriptions that refer to the same effort and output a bullet list of the key items per category.',
    'todolist.assessment.prompts.copy': 'Copy Prompt',
    'todolist.assessment.prompts.copied': 'Copied!',
    'todolist.assessment.prompts.copy_error': 'Copy failed, please copy manually.',
    'todolist.assessment.status.done': 'Completed',
    'todolist.assessment.status.todo': 'Pending',
    'todolist.copy_next': 'Cut to Next Week',
    'todolist.copy_item': 'Copy',
    'todolist.common.manage': 'Frequent Items',
    'todolist.common.title': 'Frequent Todo Items',
    'todolist.common.description': 'Maintain reusable entries that you can quickly insert while editing todos.',
    'todolist.common.empty': 'No frequent items yet. Add one to get started.',
    'todolist.common.add': 'Add Frequent Item',
    'todolist.common.save': 'Save',
    'todolist.common.delete': 'Delete',
    'todolist.common.placeholder': 'Enter frequent item',
    'todolist.common.close': 'Close',
    'todolist.common.suggestions': 'Common snippets',
    'todolist.common.match_hint_single': 'Matches common item: {item}',
    'todolist.common.match_hint_plural': 'Matches common items: {items}',
    'todolist.status.pending': 'Saving…',
    'todolist.status.success': 'Saved automatically',
    'todolist.status.error': 'Save failed, please try again',
    'todolist.delete.undo_hint': 'Todo deleted. Undo?',
    'todolist.delete.undo_button': 'Undo',
    'todolist.delete.undo_countdown': 'Undo in {seconds}s',
    'todolist.week.current': 'Current Week',
    'todolist.week.last': 'Last Week',
    'todolist.week.next': 'Next Week',
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
  'tasks.pending_warning': 'Unconfirmed member affairs, please confirm ASAP:',
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
    'task_affairs.table_status': 'Status',
    'task_affairs.table_actions': 'Actions',
    'task_affairs.action_edit': 'Edit',
    'task_affairs.action_delete': 'Delete',
    'task_affairs.edit_title': 'Edit Affair',
    'task_affairs.label_description': 'Description',
    'task_affairs.label_start': 'Start Date',
    'task_affairs.label_end': 'End Date',
    'task_affairs.label_status': 'Status',
    'task_affairs.save': 'Save',
    'task_affairs.cancel': 'Cancel',
    'task_affairs.new_title': 'New Affair',
    'task_affairs.label_members': 'Members (hold Ctrl to select multiple)',
    'task_affairs.add': 'Add Affair',
    'task_affairs.back': 'Back',
    'task_affairs.error.range': 'End date must not be earlier than start date',
    'task_affairs.workload_prefix': 'Workload: ',
    'task_affairs.workload_suffix': ' days',
    'task_affairs.ranking.title': 'Task Workload Ranking',
    'task_affairs.ranking.rank': 'Rank',
    'task_affairs.ranking.campus_id': 'Campus ID',
    'task_affairs.ranking.member': 'Member',
    'task_affairs.ranking.workload': 'Total Workload (days)',
    'task_affairs.ranking.empty': 'No workload records yet.',
    'task_affairs.confirm.delete': 'Delete affair?',
    'task_affairs.merge_selected': 'Merge Selected',
    'task_affairs.confirm.merge': 'Merge selected affairs?',
    'task_affairs.status.pending': 'Pending',
    'task_affairs.status.confirmed': 'Confirmed',
    'task_affairs.action_confirm': 'Confirm',
    'task_affairs.action_unconfirm': 'Unconfirm',
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
    'notifications.title': 'Notifications',
    'notifications.add': 'Add Notification',
    'notifications.table_content': 'Content',
    'notifications.table_begin': 'Begin',
    'notifications.table_end': 'End',
    'notifications.table_actions': 'Actions',
    'notifications.action_edit': 'Edit',
    'notifications.action_revoke': 'Revoke',
    'notifications.toggle_details': 'Show Target Details',
    'notifications.status.sent': 'Sent',
    'notifications.status.seen': 'Seen',
    'notifications.status.checked': 'Checked',
    'notifications.confirm.revoke': 'Revoke this notification?',
    'notifications.confirm.check': 'Are you sure you want to check this notification? Please make sure you have already done the matters stated in the notification.',
    'notifications.action_check': 'Check',
    'notifications.none': 'No notifications',
    'notifications.show_expired': 'Show expired notifications',
    'notifications.hide_expired': 'Hide expired notifications',
    'notifications.expired_title': 'Expired Notifications',
    'regulations.title': 'Regulations',
    'regulations.add': 'Add Regulation',
    'regulations.table_description': 'Description',
    'regulations.table_category': 'Category',
    'regulations.table_date': 'Date',
    'regulations.table_files': 'Attachments',
    'regulations.table_actions': 'Actions',
    'regulations.action_edit': 'Edit',
    'regulations.action_delete': 'Delete',
    'regulations.action_view': 'View',
    'regulations.none': 'No regulations',
    'regulations.confirm.delete': 'Delete this regulation?',
    'regulations.confirm.file_delete': 'Delete this file?',
    'regulations.file_delete': 'Delete',
    'regulation_edit.title_edit': 'Edit Regulation',
    'regulation_edit.title_add': 'Add Regulation',
    'regulation_edit.label_description': 'Description',
    'regulation_edit.label_category': 'Category',
    'regulation_edit.label_files': 'Attachments',
    'regulation_edit.save': 'Save',
    'regulation_edit.cancel': 'Cancel',
    'regulation_edit.upload_error': 'Upload failed: ',
    'notification_edit.title_edit': 'Edit Notification',
    'notification_edit.title_add': 'Add Notification',
    'notification_edit.label_content': 'Content',
    'notification_edit.label_begin': 'Begin Date',
    'notification_edit.label_end': 'End Date',
    'notification_edit.label_members': 'Target Members',
    'notification_edit.select_all': 'Select All',
    'notification_edit.save': 'Save',
    'notification_edit.cancel': 'Cancel',
    'account.msg.password_mismatch': 'New passwords do not match',
    'account.msg.password_updated': 'Password updated successfully',
    'account.msg.current_incorrect': 'Current password is incorrect',
    'account.msg.manager_added': 'Manager added',
    'account.msg.manager_add_error': 'Error adding manager',
    'account.msg.manager_deleted': 'Administrator deleted',
    'account.msg.manager_delete_error': 'Failed to delete administrator',
    'account.msg.manager_delete_self': 'You cannot delete your own account',
    'account.msg.manager_delete_last': 'At least one administrator must remain'
  ,
    'assets.title': 'Assets',
    'assets.stats.by_category': 'By Category',
    'assets.stats.by_status': 'By Status',
    'assets.stats.none': 'No data',
    'assets.inbound.title': 'Inbound Orders',
    'assets.inbound.add': 'New Inbound',
    'assets.inbound.edit': 'Edit Inbound',
    'assets.inbound.order_number': 'Order #',
    'assets.inbound.supplier': 'Supplier',
    'assets.inbound.supplier_lead': 'Supplier Lead',
    'assets.inbound.receiver_lead': 'Receiving Lead',
    'assets.inbound.location': 'Inbound Location',
    'assets.inbound.date': 'Inbound Date',
    'assets.inbound.notes': 'Notes',
    'assets.inbound.assets_count': 'Assets',
    'assets.inbound.none': 'No inbound orders',
    'assets.inbound.delete.title': 'Delete Inbound Order',
    'assets.inbound.delete.message': 'Inbound order {order} will remove {count} linked assets. Please confirm carefully.',
    'assets.inbound.delete.confirm': 'Delete this inbound order and all linked assets?',
    'assets.inbound.delete.double': 'Please confirm again: all assets linked to this inbound order will be removed.',
    'assets.list.title': 'Asset Inventory',
    'assets.list.mine': 'Assets I Manage',
    'assets.list.unassigned': 'Pending or Lost Assets',
    'assets.list.mine_empty': 'No assets assigned to you right now.',
    'assets.list.unassigned_empty': 'No pending or lost assets at the moment.',
    'assets.add': 'New Asset',
    'assets.export': 'Export to Excel',
    'assets.sync_all.button': 'Sync All',
    'assets.sync_all.title': 'Sync Multiple Assets',
    'assets.sync_all.description': 'Enter asset identifiers separated by commas, semicolons, or new lines. Use a hyphen to define ranges.',
    'assets.sync_all.inbound_label': 'Inbound Order',
    'assets.sync_all.ids_label': 'Asset Identifiers',
    'assets.sync_all.ids_placeholder': 'Examples: 001-010,015,020',
    'assets.sync_all.ids_hint': 'Use commas, semicolons, or new lines to separate identifiers. Hyphen ranges auto-fill missing numbers.',
    'assets.sync_all.results_title': 'Progress',
    'assets.sync_all.start': 'Start Sync',
    'assets.sync_all.status.preparing': 'Preparing sync list…',
    'assets.sync_all.status.running': 'Sync in progress…',
    'assets.sync_all.status.summary': 'Sync complete. Success: {success}, skipped: {skipped}, failed: {failed}.',
    'assets.sync_all.errors.inbound_required': 'Please select an inbound order.',
    'assets.sync_all.errors.input_required': 'Enter at least one asset identifier.',
    'assets.sync_all.result.success': 'Synced successfully.',
    'assets.sync_all.result.updated': 'Updated existing asset.',
    'assets.sync_all.result.skipped_exists': 'Skipped: asset already exists.',
    'assets.sync_all.result.failed': 'Failed to sync.',
    'assets.sync_all.row.pending': 'Pending',
    'assets.sync_all.row.running': 'Syncing…',
    'assets.edit': 'Edit Asset',
    'assets.table.order_number': 'Order #',
    'assets.table.asset_code': 'Asset Code',
    'assets.table.category': 'Category',
    'assets.table.model': 'Model / Configuration',
    'assets.table.organization': 'Owning Unit',
    'assets.table.remarks': 'Remarks',
    'assets.table.location': 'Current Location',
    'assets.table.owner': 'Person in Charge',
    'assets.table.status': 'Status',
    'assets.table.image': 'Photo',
    'assets.table.updated_at': 'Updated',
    'assets.table.actions': 'Actions',
    'assets.none': 'No assets',
    'assets.action.edit': 'Edit',
    'assets.action.goto': 'GoTo',
    'assets.action.delete': 'Delete',
    'assets.action.confirm_delete': 'Confirm Delete',
    'assets.form.inbound': 'Inbound Order',
    'assets.form.inbound_placeholder': 'Select inbound order',
    'assets.form.asset_code': 'Asset Code',
    'assets.form.asset_code_suffix_placeholder': 'Leave blank to auto-generate',
    'assets.form.sync': 'Sync',
    'assets.form.sync_status.loading': 'Loading asset data…',
    'assets.form.sync_status.success': 'Asset data synced successfully.',
    'assets.form.sync_status.error': 'Unable to load asset data.',
    'assets.form.status': 'Status',
    'assets.form.category': 'Category',
    'assets.form.model': 'Model / Configuration',
    'assets.form.organization': 'Owning Unit',
    'assets.form.remarks': 'Remarks',
    'assets.form.office': 'Current Office',
    'assets.form.seat': 'Workstation',
    'assets.form.owner': 'Person in Charge',
    'assets.form.owner_other': 'Others',
    'assets.form.owner_other_placeholder': 'Enter responsible person',
    'assets.form.owner_other_hint': 'If the responsible person is not listed, enter the name manually.',
    'assets.form.image': 'Asset Photo',
    'assets.form.image_hint': 'Upload an asset photo for verification.',
    'assets.form.image_remote': 'Synced image preview',
    'assets.form.none': 'None',
    'assets.form.reuse_prompt': 'Reuse the last filled category and configuration?',
    'assets.form.office_option.region_label': 'Region: {region}',
    'assets.form.office_option.location_label': 'Location: {location}',
    'assets.form.office_option.main_separator': ' — ',
    'assets.form.office_option.sub_separator': ' / ',
    'assets.logs.title': 'Operation History',
    'assets.logs.empty': 'No history yet',
    'assets.status.in_use': 'In Use',
    'assets.status.maintenance': 'Under Maintenance',
    'assets.status.pending': 'Pending Allocation',
    'assets.status.lost': 'Lost',
    'assets.status.retired': 'Retired',
    'assets.save': 'Save',
    'assets.cancel': 'Cancel',
    'assets.delete.title': 'Delete Asset',
    'assets.delete.message': 'Delete asset {code}? This cannot be undone.',
    'assets.delete.confirm': 'Delete this asset?',
    'assets.messages.permission_denied': 'You do not have permission to perform this operation.',
    'assets.messages.order_required': 'Order number is required.',
    'assets.messages.order_exists': 'Order number already exists.',
    'assets.messages.inbound_missing': 'Inbound order not found.',
    'assets.messages.inbound_created': 'Inbound order created successfully.',
    'assets.messages.inbound_updated': 'Inbound order updated successfully.',
    'assets.messages.inbound_deleted': 'Inbound order deleted successfully.',
    'assets.messages.asset_missing': 'Asset not found.',
    'assets.messages.asset_created': 'Asset created successfully.',
    'assets.messages.asset_updated': 'Asset updated successfully.',
    'assets.messages.owner_external_required': 'Please enter the responsible person name.',
    'assets.messages.asset_deleted': 'Asset deleted successfully.',
    'assets.messages.settings_saved': 'Settings updated successfully.',
    'assets.messages.asset_code_exists': 'Asset code already exists.',
    'assets.messages.invalid_seat': 'Selected seat does not exist.',
    'assets.messages.seat_office_mismatch': 'Selected seat does not belong to the chosen office.',
    'assets.messages.image_upload_failed': 'Asset photo upload failed.',
    'assets.messages.invalid_image': 'Uploaded file is not a valid image.',
    'assets.messages.generic_error': 'Operation failed, please try again later.',
    'assets.settings.title': 'General Settings',
    'assets.settings.description': 'Configure global options for asset management.',
    'assets.settings.code_prefix': 'Asset Code Prefix',
    'assets.settings.code_prefix_hint': 'Shown before the asset code input and combined with the suffix.',
    'assets.settings.link_prefix': 'Asset Link Prefix',
    'assets.settings.link_prefix_hint': 'Prepended to the code suffix to open the external asset platform.',
    'assets.settings.sync_api_prefix': 'Sync API Prefix',
    'assets.settings.sync_api_prefix_hint': 'Append the asset code suffix to query the integration endpoint.',
    'assets.settings.save': 'Save Settings',
    'assets.settings.open_modal': 'Manage General Settings',
    'assets.sync.title': 'Sync Interface',
    'assets.sync.description': 'After saving the prefix, load a sample asset to map JSON keys to local fields.',
    'assets.sync.prefix_notice': 'Provide and save the sync API prefix to start configuring mappings.',
    'assets.sync.sample_input_label': 'Sample Asset ID',
    'assets.sync.sample_input_placeholder': 'Enter asset ID or code',
    'assets.sync.sample_help': 'The asset code suffix (without the prefix and leading zeros) will be appended to the sync API prefix.',
    'assets.sync.load_button': 'Load Sample',
    'assets.sync.sample_result_title': 'Sample Response',
    'assets.sync.sample_url': 'Requested URL:',
    'assets.sync.mapping_title': 'Attribute Mapping',
    'assets.sync.mapping_description': 'Select the JSON key that matches each asset field.',
    'assets.sync.mapping.attribute': 'Attribute',
    'assets.sync.mapping.json_key': 'JSON Key',
    'assets.sync.mapping.none': 'Not linked',
    'assets.sync.save_button': 'Save Mapping',
    'assets.sync.no_keys': 'No scalar values detected in the sample response.',
    'assets.sync.status.loading': 'Loading sample data…',
    'assets.sync.status.loaded': 'Sample loaded. Review the payload and update the mapping.',
    'assets.sync.status.saving': 'Saving mapping…',
    'assets.sync.status.saved': 'Mapping saved successfully.',
    'assets.sync.status.error': 'Operation failed. Please try again.',
    'assets.sync.errors.prefix_missing': 'Configure the sync API prefix before loading a sample.',
    'assets.sync.errors.asset_required': 'Please enter a sample asset ID.',
    'assets.sync.errors.asset_suffix_empty': 'Unable to determine the asset identifier without the prefix.',
    'assets.sync.errors.fetch_failed': 'Failed to request the sync API.',
    'assets.sync.errors.http': 'The sync API returned an unexpected status.',
    'assets.sync.errors.invalid_json': 'The sync API response is not valid JSON.',
    'assets.sync.errors.mapping_missing': 'Configure the sync mapping before using this feature.',
    'assets.sync.errors.unknown': 'Unexpected sync error occurred.',
    'assets.sync.attributes.asset_code': 'Asset Code Suffix',
    'assets.sync.attributes.category': 'Category',
    'assets.sync.attributes.model': 'Model / Configuration',
    'assets.sync.attributes.organization': 'Owning Unit',
    'assets.sync.attributes.remarks': 'Remarks',
    'assets.sync.attributes.status': 'Status',
    'assets.sync.attributes.owner': 'Responsible Person',
    'assets.sync.attributes.office': 'Office Label',
    'assets.sync.attributes.seat': 'Workstation Label',
    'assets.sync.attributes.image': 'Image URL',
    'assets.assignments.title': 'Member Asset Responsibilities',
    'assets.assignments.member': 'Member',
    'assets.assignments.asset_code': 'Asset Code',
    'assets.assignments.organization': 'Owning Unit',
    'assets.assignments.category': 'Category',
    'assets.assignments.model': 'Model / Configuration',
    'assets.assignments.location': 'Location',
    'assets.assignments.status': 'Status',
    'assets.assignments.updated_at': 'Updated',
    'assets.assignments.none': 'No member asset data',
    'assets.assignments.member_empty': 'No assets assigned.',
    'assets.assignments.badge': '{code} - {category} - {model}',
    'assets.assignments.owner_external': 'External / Others',
    'assets.assignments.owner_missing': 'Unassigned',
    'assets.assignments.other_title': 'Other Asset Records',
    'assets.assignments.other_none': 'No additional assets pending review.'
  ,
  'reimburse.title': 'Reimbursement Batches',
  'reimburse.add_batch': 'Add Batch',
  'reimburse.table_title': 'Title',
  'reimburse.table_deadline': 'Deadline',
  'reimburse.table_incharge': 'In Charge',
  'reimburse.table_actions': 'Actions',
  'reimburse.action_details': 'Details',
  'reimburse.action_download': 'Download',
  'reimburse.action_edit': 'Edit',
  'reimburse.batch.title': 'Title',
  'reimburse.batch.incharge': 'In Charge',
  'reimburse.batch.deadline': 'Deadline',
  'reimburse.batch.save': 'Save',
  'reimburse.batch.cancel': 'Cancel',
  'reimburse.batch.file': 'Receipt File',
  'reimburse.batch.amount': 'Amount',
  'reimburse.batch.upload': 'Upload',
  'reimburse.batch.autofill': 'Auto Fill',
  'reimburse.batch.receipt': 'Receipt',
  'reimburse.batch.uploader': 'Uploader',
  'reimburse.batch.actions': 'Actions',
  'reimburse.batch.delete': 'Delete',
  'reimburse.batch.confirm_delete': 'Delete receipt?',
  'reimburse.batch.confirm_delete_batch': 'Delete batch?',
  'reimburse.batch.deadline_passed': 'Deadline passed',
  'reimburse.batch.none': 'None',
  'reimburse.table_myreceipts': 'My Receipts',
  'reimburse.batch.limit': 'Price Limit',
  'reimburse.batch.allowed_types': 'Allowed Types',
  'reimburse.batch.category': 'Category',
  'reimburse.batch.description': 'Description',
  'reimburse.batch.price': 'Price',
  'reimburse.batch.upload_date': 'Upload Date',
  'reimburse.batch.status': 'Status',
  'reimburse.category.office': 'Office Stuff',
  'reimburse.category.electronic': 'Electronic Gadget',
  'reimburse.category.membership': 'Membership',
  'reimburse.category.book': 'Book',
  'reimburse.category.trip': 'Trip',
  'reimburse.batch.edit': 'Edit Receipt',
  'reimburse.batch.refuse': 'Refuse',
  'reimburse.batch.confirm_refuse': 'Refuse receipt?',
  'reimburse.refused.list': 'Refused Receipts',
  'reimburse.refused.title': 'Refused Receipts',
  'reimburse.refused.original_batch': 'Original Batch',
  'reimburse.status.refused': 'refused',
  'reimburse.batch.batch': 'Batch',
  'reimburse.batch.file_required': 'File required',
  'reimburse.batch.manager_no_upload': 'Managers cannot upload receipts',
  'reimburse.batch.limit_exceed': 'Price exceeds limit',
  'reimburse.batch.type_not_allowed': 'Type not allowed',
  'reimburse.batch.prohibited': 'Receipt contains prohibited content',
  'reimburse.batch.check_warning': 'You should carefully check the content of each receipt and refuse those unqualified receipts before proceeding to the next step',
  'reimburse.batch.notice.title': 'Batch Notice',
  'reimburse.batch.notice.editable': 'Editable by admins or the person in charge',
  'reimburse.batch.notice.empty': 'No notice yet.',
  'reimburse.batch.notice.edit_title': 'Edit Batch Notice',
  'reimburse.batch.notice.edit_button': 'Edit Notice',
  'reimburse.batch.notice.en': 'Notice (English)',
  'reimburse.batch.notice.zh': 'Notice (Chinese)',
  'reimburse.batch.notice.hint': 'Visible to everyone; only managers and the person in charge can edit.',
  'reimburse.batch.notice.cancel': 'Cancel',
  'reimburse.batch.notice.save': 'Save Notice',
  'reimburse.status.submitted': 'submitted',
  'reimburse.status.locked': 'locked',
  'reimburse.status.complete': 'complete',
  'reimburse.status.open': 'open',
  'reimburse.status.completed': 'completed',
  'reimburse.batch.lock': 'Lock Batch',
  'reimburse.batch.complete': 'Complete Batch',
  'reimburse.batch.unlock': 'Unlock Batch',
  'reimburse.batch.reopen': 'Reopen Batch',
  'reimburse.batch.description_required': 'Description required',
  'reimburse.batch.total_member': 'Total by Member',
  'reimburse.batch.total_category': 'Total by Category',
  'reimburse.batch.logs': 'Change Log',
  'reimburse.batch.campus_id': 'Campus ID',
  'reimburse.batch.total': 'Total',
  'reimburse.active.none': 'No active batches',
  'reimburse.completed.show': 'Show completed batches',
  'reimburse.completed.hide': 'Hide completed batches',
  'reimburse.completed.title': 'Completed Batches',
  'reimburse.batch.logs.empty': 'No log entries',
  'reimburse.batch.logs.prev': 'Previous',
  'reimburse.batch.logs.next': 'Next',
  'reimburse.batch.logs.page_label': 'Page',
  'reimburse.keywords.manage': 'Prohibited Keywords',
  'reimburse.keywords.add': 'Add',
  'reimburse.keywords.word': 'Keyword',
    'reimburse.announcement.title': 'Reimbursement Announcement',
    'reimburse.announcement.label_en': 'English Announcement',
    'reimburse.announcement.label_zh': 'Chinese Announcement',
    'reimburse.announcement.edit': 'Edit Announcement',
    'reimburse.announcement.note_html': 'You can use HTML tags for styling.',

    'collect.title': 'Collect',
    'collect.add_template': 'New Form',
    'collect.edit_template': 'Edit Form',
    'collect.name': 'Form Name',
    'collect.description': 'Description',
    'collect.status': 'Status',
    'collect.status.open': 'Open',
    'collect.status.paused': 'Paused',
    'collect.status.ended': 'Ended',
    'collect.status.void': 'Voided',
    'collect.deadline': 'Deadline',
    'collect.targets': 'Target Members',
    'collect.member_selector.title': 'On-duty Members',
    'collect.member_selector.subtitle': 'Pick the members who need to fill this form.',
    'collect.fields': 'Fields',
    'collect.field_label': 'Label',
    'collect.field_type': 'Type',
    'collect.field_required': 'Required',
    'collect.field_options': 'Options (for dropdown, separated by commas)',
    'collect.field_add': 'Add Field',
    'collect.field_types.number': 'Number',
    'collect.field_types.text': 'Text',
    'collect.field_types.select': 'Dropdown',
    'collect.field_types.file': 'File',
    'collect.actions': 'Actions',
    'collect.save': 'Save',
    'collect.cancel': 'Cancel',
    'collect.template_card.assignees': 'Assignees',
    'collect.template_card.submissions': 'Submissions',
    'collect.template_card.pending': 'Pending',
    'collect.template_card.download': 'Download ZIP',
    'collect.template_card.manage': 'Manage',
    'collect.template_card.fill': 'Fill Form',
    'collect.template_card.records': 'My Records',
    'collect.template_card.new_record': 'Add Record',
    'collect.template_card.no_records': 'No records yet.',
    'collect.template_card.members_pending': 'Not submitted',
    'collect.template_card.members_done': 'Submitted',
    'collect.template_card.zip_label': 'Export data & files',
    'collect.template_card.edit': 'Edit',
    'collect.template_card.delete': 'Delete',
    'collect.template_card.status_label': 'Form status',
    'collect.template_card.access_limited': 'Only people selected by the admin can fill in.',
    'collect.template_card.status_hint': 'Only open forms can be filled.',
    'collect.hide_archived': 'Hide ended/void forms',
    'collect.show_archived': 'Show ended/void forms',
    'collect.submit_record': 'Submit',
    'collect.update_record': 'Update',
    'collect.file_current': 'Current file',
    'collect.file_replace': 'Upload to replace',
    'collect.member_only': 'You are not allowed to fill this form.',
    'collect.access_denied': 'Only open forms assigned to you can be filled.',
    'collect.confirm_delete': 'Are you sure to delete this form? Data will be removed.',
    'collect.download_zip': 'Download ZIP',
    'collect.none': 'None',
    'collect.record_created': 'Record saved',
    'collect.record_updated': 'Record updated',
    'collect.record_deleted': 'Record deleted',
    'collect.record_failed': 'Operation failed',
    'collect.delete_record': 'Delete',
    'collect.confirm_delete_record': 'Delete this record? This cannot be undone.',
    'collect.toast_close': 'Close'
  },
  zh: {
    'nav.home': '团队管理',
    'nav.members': '成员',
    'nav.askme': '问问',
    'nav.todolist': '待办',
    'nav.projects': '项目',
    'nav.directions': '研究',
    'nav.offices': '地点',
    'nav.collect': '收集',
    'nav.notifications': '通知',
    'nav.reimburse': '报销',
    'nav.assets': '资产',
    'nav.tasks': '任务',
    'nav.workload': '统计',
    'nav.account': '管理',
    'askme.title': '不懂问我',
    'askme.subtitle': '一键搜索政策流程、办公地点、固定资产与知识库内容。',
    'askme.manage': '管理知识库',
    'askme.search_label': '搜索',
    'askme.search_placeholder': '请输入你的问题…',
    'askme.search_btn': '搜索',
    'askme.searching': '搜索中…',
    'askme.hint': '提示：尝试输入“出差”“办公室”“设备”等关键词。',
    'askme.no_results': '暂未找到匹配的知识条目。',
    'askme.show_detail': '展开查看详情',
    'askme.hide_detail': '收起详情',
    'askme.download': '下载文档',
    'askme.office_members': '办公室相关人员',
    'askme.manage_title': '知识库管理',
    'askme.content_label': '知识内容',
    'askme.content_hint': '这里不做多语言区分，一条内容即可。',
    'askme.keywords_label': '候选关键词（逗号分隔）',
    'askme.keywords_hint': '多填一些关键词，能提高模糊匹配效果。',
    'askme.save': '保存',
    'askme.cancel': '取消',
    'askme.table.content': '内容',
    'askme.table.keywords': '关键词',
    'askme.table.actions': '操作',
    'askme.no_entries': '暂无知识库条目。',
    'askme.edit': '编辑',
    'askme.delete': '删除',
    'welcome': '欢迎',
    'logout': '退出登录',
    'header.title': '团队管理平台',
    'qr.scan': '扫码进入',
    'qr.copy': '复制链接',
    'login.title': '成员登录',
    'login.title.manager': '管理员登录',
    'login.title.member': '成员登录',
    'login.section.manager.title': '管理员入口',
    'login.section.manager.description': '使用管理员账号登录，管理团队、项目、通知等功能。',
    'login.section.member.title': '成员入口',
    'login.section.member.description': '使用您在仪表板中设置的登录方式完成验证。',
    'login.switch.label': '切换登录入口',
    'login.switch.member': '成员登录',
    'login.switch.manager': '管理员登录',
    'login.username': '用户名',
    'login.password': '密码',
    'login.button': '登录',
    'login.button.manager': '管理员登录',
    'login.button.member': '成员登录',
    'login.radio.manager': '管理员',
    'login.radio.member': '一般成员',
    'login.warning.manager': '您正在以管理员身份登录。',
    'login.warning.member': '您正在以普通成员身份登录。',
    'login.name': '姓名',
    'login.identity': '身份证号',
    'login.member.mode.title': '登录方式',
    'login.member.mode.identity': '身份证号',
    'login.member.mode.password': '密码',
    'login.member.mode.identity_hint': '使用档案中存储的身份证号进行验证。',
    'login.member.mode.password_hint': '使用您在仪表板中设置的密码进行验证。',
    'login.placeholder.username': '用户名',
    'login.placeholder.password': '密码',
    'login.placeholder.name': '姓名',
    'login.placeholder.identity': '身份证号',
    'login.error.manager_invalid': '用户名或密码不正确。',
    'login.error.member_name_required': '请输入姓名。',
    'login.error.member_not_found': '未找到对应账号，请确认登录方式。',
    'login.error.member_identity_required': '请输入身份证号。',
    'login.error.member_identity_invalid': '身份证号验证失败。',
    'login.error.member_password_required': '请输入密码。',
    'login.error.member_password_invalid': '密码验证失败。',
    'login.error.member_mode_mismatch': '该账号已选择其他登录方式，请调整后重试。',
    'member.login_settings.title': '登录方式设置',
    'member.login_settings.description': '选择下次登录成员门户时使用的验证方式。',
    'member.login_settings.current_label': '当前登录方式：',
    'member.login_settings.manage_button': '管理登录方式',
    'member.login_settings.method_label': '首选登录方式',
    'member.login_settings.method.identity': '身份证号',
    'member.login_settings.method.password': '密码',
    'member.login_settings.method.identity_hint': '继续使用档案中保存的身份证号进行登录。',
    'member.login_settings.method.password_hint': '改为使用下方设置的密码登录，身份证号登录将被禁用。',
    'member.login_settings.password.label': '新密码',
    'member.login_settings.password.confirm': '确认密码',
    'member.login_settings.password.placeholder': '请输入新密码',
    'member.login_settings.password.confirm_placeholder': '再次输入新密码',
    'member.login_settings.submit': '保存设置',
    'member.login_settings.success_identity': '已启用身份证号登录。',
    'member.login_settings.success_password': '已启用密码登录，请使用新密码登录。',
    'member.login_settings.error_password_required': '请输入并确认新密码以启用密码登录。',
    'member.login_settings.error_password_mismatch': '两次输入的密码不一致。',
    'member.login_settings.error_invalid_method': '不支持的登录方式。',
    'member.login_settings.current.identity': '当前方式：身份证号登录',
    'member.login_settings.current.password': '当前方式：密码登录',
    'account.title': '账户设置',
    'account.hero.title': '管理员中心',
    'account.hero.subtitle': '帮助团队安全、有序地管理后台账号。',
    'account.change_password': '修改密码',
    'account.current_password': '当前密码',
    'account.new_password': '新密码',
    'account.confirm_password': '确认新密码',
    'account.change_password_btn': '修改密码',
    'account.section.security_hint': '定期更新密码以保护敏感信息。',
    'account.add_manager': '添加管理员',
    'account.section.add_hint': '为协作伙伴创建额外的管理员账号。',
    'account.username': '用户名',
    'account.password': '密码',
    'account.add_manager_btn': '添加管理员',
    'account.section.list_title': '管理员列表',
    'account.section.list_hint': '查看所有拥有后台访问权限的账号，及时移除不再需要的管理员。',
    'account.manager_id_label': '编号',
    'account.manager_list_empty': '暂无管理员',
    'account.badge.you': '当前账号',
    'account.delete': '删除',
    'account.delete_disabled': '不能删除当前登录账号',
    'account.delete_confirm': '确定要删除该管理员吗？此操作无法撤销。',
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
    'project_edit.error_generic': '保存项目失败，请稍后再试。',
    'project_edit.not_found': '未找到该项目',
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
    'offices.title': '办公地点',
    'offices.add': '新增办公地点',
    'offices.summary.title': '工位汇总',
    'offices.summary.total_seats': '当前工位总数',
    'offices.summary.available_seats': '剩余待分配工位数',
    'offices.summary.active_members': '当前在岗成员数',
    'offices.summary.unassigned_members': '暂无工位成员数',
    'offices.table.name': '办公室名称',
    'offices.table.location': '具体位置描述',
    'offices.table.region': '所属区域',
    'offices.table.seats': '工位数量',
    'offices.table.available': '剩余工位',
    'offices.table.members': '当前成员',
    'offices.action.view': '查看布局',
    'offices.action.edit': '编辑',
    'offices.action.delete': '删除',
    'offices.none': '暂无',
    'offices.selection.closed': '选座已关闭',
    'offices.selection.not_whitelisted': '仅限白名单',
    'offices.members_overview.title': '成员办公分布',
    'offices.members_overview.member': '成员',
    'offices.members_overview.year_of_join': '入学年份',
    'offices.members_overview.degree': '当前学历',
    'offices.members_overview.offices': '办公地点与工位',
    'offices.members_overview.none': '无',
    'offices.status.available': '可用',
    'offices.status.occupied': '已占用',
    'office_edit.title_add': '新增办公地点',
    'office_edit.title_edit': '编辑办公地点',
    'office_edit.label_name': '办公室名称',
    'office_edit.label_location': '具体位置描述',
    'office_edit.label_region': '所属区域',
    'office_edit.label_image': '布局图片',
    'office_edit.label_seats': '工位布局',
    'office_edit.label_open_selection': '允许成员自助选座',
    'office_edit.open_selection_hint': '关闭后仅管理员可以调整座位。',
    'office_edit.whitelist.title': '可选座位成员白名单',
    'office_edit.whitelist.description': '仅白名单内的在岗成员可以在本办公室选座。',
    'office_edit.whitelist.search_placeholder': '搜索成员…',
    'office_edit.whitelist.select_all': '全选',
    'office_edit.whitelist.clear': '清空',
    'office_edit.whitelist.empty': '暂无在岗成员。',
    'office_edit.instructions': '在布局上点击添加工位，可拖动图标微调位置。',
    'office_edit.instructions_remove': '可在下方列表中重命名或删除工位。',
    'office_edit.table.label': '工位名称',
    'office_edit.table.actions': '操作',
    'office_edit.remove': '删除',
    'office_edit.default_label': '工位',
    'office_edit.no_image': '请先上传布局图片再添加工位。',
    'office_edit.current_image': '当前布局',
    'office_edit.seats_empty': '尚未设置工位。',
    'office_edit.save': '保存',
    'office_edit.cancel': '取消',
    'office_view.title': '办公布局',
    'office_view.info.location': '位置',
    'office_view.info.region': '区域',
    'office_view.info.total': '总工位',
    'office_view.info.available': '剩余工位',
    'office_view.info.selection': '选座状态',
    'office_view.instructions.member': '点击空闲工位即可认领，再次点击自己的工位可释放。',
    'office_view.instructions.manager': '选择成员后点击工位即可指定，选择“清空工位”可释放。',
    'office_view.instructions.closed': '当前选座已关闭，如需调整请联系管理员。',
    'office_view.instructions.not_allowed': '您不在该办公室的可选座位白名单内。',
    'office_view.select.member': '选择成员',
    'office_view.select.clear': '清空工位',
    'office_view.table.seat': '工位',
    'office_view.table.status': '状态',
    'office_view.table.member': '成员',
    'office_view.status.available': '可用',
    'office_view.status.occupied': '已占用',
    'office_view.member.empty': '未分配',
    'office_view.message.select_member': '请先选择成员。',
    'office_view.message.unavailable': '该工位已被占用。',
    'office_view.message.no_permission': '您只能管理自己的工位。',
    'office_view.message.error': '操作失败，请重试。',
    'office_view.message.closed': '当前选座已关闭。',
    'office_view.message.not_allowed': '您无权管理该办公室的座位。',
    'office_view.selection.open': '开放',
    'office_view.selection.closed': '关闭',
    'office_view.selection.not_whitelisted': '仅限白名单',
    'project_members.title_prefix': '项目成员 -',
    'project_members.current_members': '当前成员',
    'project_members.join_date': '入项日期',
    'project_members.remove': '移除',
    'project_members.add_member': '新增成员',
    'project_members.label_member': '成员',
    'project_members.select_member': '选择成员',
    'project_members.label_join': '入项日期',
    'project_members.label_exit': '退出日期',
    'project_members.save': '新增',
    'project_members.back': '返回',
    'project_members.history_title': '成员变动历史',
    'project_members.history_member': '成员',
    'project_members.history_join': '入项日期',
    'project_members.history_exit': '退出日期',
    'project_members.remove_confirm_title': '移除成员',
    'project_members.remove_confirm': '请确认该成员的退出日期。',
    'project_members.invalid_request': '操作失败，请稍后再试。',
    'project_members.missing_project': '未指定项目。',
    'members_import.title': '从 CSV 导入成员',
    'members_import.import': '导入',
    'members_import.cancel': '取消',
    'members_import.download_template': '下载导入模板',
    'members_import.back': '返回成员列表',
    'members_import.preview.title': '导入预览',
    'members_import.preview.summary.new': '新增记录',
    'members_import.preview.summary.update': '将覆盖更新',
    'members_import.preview.summary.skip': '跳过记录',
    'members_import.preview.update_warning': '存在同一卡号的成员，将更新其信息，请仔细核对。',
    'members_import.preview.column.campus_id': '一卡通号',
    'members_import.preview.column.name': '姓名',
    'members_import.preview.column.status': '处理结果',
    'members_import.preview.column.issues': '识别问题',
    'members_import.preview.action.create': '新增',
    'members_import.preview.action.update': '更新',
    'members_import.preview.action.skip': '跳过',
    'members_import.preview.ack_updates': '我已知晓会更新已有成员数据。',
    'members_import.preview.confirm': '确认导入',
    'members_import.preview.restart': '重新上传文件',
    'members_import.upload.title': '上传 CSV 文件',
    'members_import.upload.hint': '请先下载模板填写数据，仅支持 CSV 格式。',
    'members_import.upload.label': '选择 CSV 文件',
    'members_import.upload.preview': '预览导入',
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
    'index.notifications': '通知列表',
    'index.pending_notifications.title': '待处理通知',
    'index.pending_notifications.description': '你还有尚未处理的通知，请及时完成。',
    'index.pending_notifications.maybe_later': '稍后处理',
    'index.regulations': '政策与流程',
    'theme.dark': '暗色',
    'theme.light': '亮色',
    'bold_font': '加粗字体',
    'members.title': '团队成员',
    'members.add': '新增成员',
  'members.import': '从表格导入',
  'members.export': '导出至表格',
  'members.request_update': '请求信息更新',
  'members.toggle_color': '切换颜色',
  'members.extra.edit': '编辑额外属性',
  'members.extra.modal_title': '编辑额外属性',
  'members.extra.description': '这些额外属性会展示在成员列表以及新增、编辑和信息更新页面。',
  'members.extra.add': '新增属性',
  'members.extra.cancel': '取消',
  'members.extra.save': '保存',
  'members.extra.empty': '暂无额外属性。',
  'members.extra.validation': '请为每个属性提供中文或英文名称。',
  'members.extra.save_error': '保存失败，请稍后重试。',
  'members.extra.section_title': '额外属性',
  'members.extra.field.name_zh': '中文名称',
  'members.extra.field.name_en': '英文名称',
  'members.extra.field.type': '属性类型',
  'members.extra.field.default_value': '默认值',
  'members.extra.type.text': '文本',
  'members.extra.type.media': '多媒体（图片、压缩包等）',
  'members.extra.helper.media_input': '可上传图片、压缩包等文件，默认值可留空。',
  'members.extra.current_file': '当前文件',
  'members.extra.no_file': '暂无上传的文件',
  'members.extra.selected_file': '已选择文件',
  'members.extra.media.preview_image': '查看图片',
  'members.extra.media.download_file': '查看或下载文件',
  'members.extra.clear_file': '清除文件',
  'members.extra.will_clear': '保存后将删除当前文件',
  'members.extra.delete': '删除',
  'members.summary.title': '成员汇总',
  'members.summary.in_work_total': '当前在岗总人数',
  'members.summary.by_degree': '按当前学历统计在岗人数',
  'members.summary.degree.unknown': '未填写',
  'members.summary.none': '当前没有在岗成员。',
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
    'todolist.prev_week': '看上周',
    'todolist.next_week': '看下周',
    'todolist.cut_tomorrow': '鸽明天',
    'todolist.drag_handle': '拖动调整顺序',
    'todolist.assessment': '待办统计',
    'todolist.assessment.generate': '统计',
    'todolist.assessment.no_items': '无待办事项',
    'todolist.assessment.export_txt': '导出TXT',
    'todolist.assessment.exporting': '正在导出…',
    'todolist.assessment.export_error': '导出失败，请重试。',
    'todolist.assessment.export_missing_range': '请先选择开始和结束日期，再导出。',
    'todolist.assessment.prompts.title': 'AI 提示词备选',
    'todolist.assessment.prompts.helper_badge': 'AI 助手',
    'todolist.assessment.prompts.open': 'AI 提示词',
    'todolist.assessment.prompts.close': '关闭',
    'todolist.assessment.prompts.description': '复制以下任一提示词，指示 AI 工具总结在 {start} 至 {end} 期间三大分类中的关键事项，并关联归纳描述不同但本质相同的事务。',
    'todolist.assessment.prompts.item1': '请扮演专业周报整理助手，基于我在 {start} 至 {end} 期间记录的待办事项，分别在“工作”“私人”“长期”三类下列出最具价值的事件，注意识别不同描述但属于同一事务的情况并合并。',
    'todolist.assessment.prompts.item2': '请帮我复盘 {start} 到 {end} 的待办记录，按照“工作”“私人”“长期”归纳关键成果，并将措辞不同但实为同一件事的条目整合成统一结论，逐条列出。',
    'todolist.assessment.prompts.item3': '基于 {start} - {end} 区间的待办事项，请总结三大分类下最具代表性的行动；若同一事务出现多个描述，请关联合并后再输出每类的要点列表。',
    'todolist.assessment.prompts.copy': '复制提示词',
    'todolist.assessment.prompts.copied': '已复制！',
    'todolist.assessment.prompts.copy_error': '复制失败，请手动复制。',
    'todolist.assessment.status.done': '已完成',
    'todolist.assessment.status.todo': '未完成',
    'todolist.copy_next': '鸽下周',
    'todolist.copy_item': '复制',
    'todolist.common.manage': '常用事项',
    'todolist.common.title': '常用事项库',
    'todolist.common.description': '维护常用事项，在填写待办时可快速插入。',
    'todolist.common.empty': '暂无常用事项，请新增。',
    'todolist.common.add': '新增常用事项',
    'todolist.common.save': '保存',
    'todolist.common.delete': '删除',
    'todolist.common.placeholder': '请输入常用事项',
    'todolist.common.close': '关闭',
    'todolist.common.suggestions': '常用事项候选',
    'todolist.common.match_hint_single': '匹配常用事项：{item}',
    'todolist.common.match_hint_plural': '匹配常用事项：{items}',
    'todolist.status.pending': '保存中…',
    'todolist.status.success': '已自动保存',
    'todolist.status.error': '保存失败，请稍后重试',
    'todolist.delete.undo_hint': '待办事项已删除，是否撤销？',
    'todolist.delete.undo_button': '撤销',
    'todolist.delete.undo_countdown': '{seconds} 秒后删除',
    'todolist.week.current': '本周',
    'todolist.week.last': '上一周',
    'todolist.week.next': '下一周',
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
    'tasks.pending_warning': '以下任务存在未确认的事务，请尽快确认：',
    'tasks.table_title': '任务标题',
    'tasks.table_start': '开始日期',
    'tasks.table_status': '状态',
    'tasks.table_actions': '操作',
    'tasks.action_edit': '编辑信息',
    'tasks.action_affairs': '下辖具体事务',
    'tasks.action_fill': '点此自申报',
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
    'task_affairs.table_status': '状态',
    'task_affairs.table_actions': '操作',
    'task_affairs.action_edit': '编辑',
    'task_affairs.action_delete': '删除',
    'task_affairs.edit_title': '编辑事务',
    'task_affairs.label_description': '具体事务描述',
    'task_affairs.label_start': '起始日期',
    'task_affairs.label_end': '结束日期',
    'task_affairs.label_status': '状态',
    'task_affairs.save': '保存',
    'task_affairs.cancel': '取消',
    'task_affairs.new_title': '新建具体事务',
    'task_affairs.label_members': '负责成员 (按住Ctrl键点选多个人)',
    'task_affairs.add': '新增事务',
    'task_affairs.back': '返回',
    'task_affairs.error.range': '结束日期必须不早于起始日期',
    'task_affairs.workload_prefix': '本次事务工作量：',
    'task_affairs.workload_suffix': ' 天',
    'task_affairs.ranking.title': '任务工作量排行榜',
    'task_affairs.ranking.rank': '排名',
    'task_affairs.ranking.campus_id': '一卡通号',
    'task_affairs.ranking.member': '成员',
    'task_affairs.ranking.workload': '累计工作量（天）',
    'task_affairs.ranking.empty': '暂无工作量记录。',
    'task_affairs.confirm.delete': '删除事务?',
    'task_affairs.merge_selected': '合并选择的事务',
    'task_affairs.confirm.merge': '合并已选事务？',
    'task_affairs.status.pending': '待确认',
    'task_affairs.status.confirmed': '已确认',
    'task_affairs.action_confirm': '确认',
    'task_affairs.action_unconfirm': '撤回确认',
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
    'notifications.title': '定向通知',
    'notifications.add': '新增通知',
    'notifications.table_content': '通知内容',
    'notifications.table_begin': '起始期',
    'notifications.table_end': '截止期',
    'notifications.table_actions': '操作',
    'notifications.action_edit': '编辑',
    'notifications.action_revoke': '撤销',
    'notifications.toggle_details': '显示目标成员',
    'notifications.status.sent': '已发送',
    'notifications.status.seen': '已阅',
    'notifications.status.checked': '已处理',
    'notifications.confirm.revoke': '确定撤销该通知？',
    'notifications.confirm.check': '确定要确认该通知吗？请确保你已完成通知中的事项。',
    'notifications.action_check': '标记已处理',
    'notifications.none': '暂无通知',
    'notifications.show_expired': '展开已到期通知',
    'notifications.hide_expired': '收起已到期通知',
    'notifications.expired_title': '已到期通知',
    'regulations.title': '政策与流程',
    'regulations.add': '新增条目',
    'regulations.table_description': '描述',
    'regulations.table_category': '类别',
    'regulations.table_date': '更新日期',
    'regulations.table_files': '附件',
    'regulations.table_actions': '操作',
    'regulations.action_edit': '编辑',
    'regulations.action_delete': '删除',
    'regulations.action_view': '查看',
    'regulations.none': '暂无条目',
    'regulations.confirm.delete': '删除该条目？',
    'regulations.confirm.file_delete': '删除该附件？',
    'regulations.file_delete': '删除',
    'regulation_edit.title_edit': '编辑政策与流程',
    'regulation_edit.title_add': '新增政策与流程',
    'regulation_edit.label_description': '描述',
    'regulation_edit.label_category': '类别',
    'regulation_edit.label_files': '附件',
    'regulation_edit.save': '保存',
    'regulation_edit.cancel': '取消',
    'regulation_edit.upload_error': '上传失败：',
    'notification_edit.title_edit': '编辑通知',
    'notification_edit.title_add': '新增通知',
    'notification_edit.label_content': '通知内容',
    'notification_edit.label_begin': '起始日期',
    'notification_edit.label_end': '截止日期',
    'notification_edit.label_members': '目标成员',
    'notification_edit.select_all': '全选',
    'notification_edit.save': '保存',
    'notification_edit.cancel': '取消',
    'account.msg.password_mismatch': '两次新密码不一致',
    'account.msg.password_updated': '密码更新成功',
    'account.msg.current_incorrect': '当前密码错误',
    'account.msg.manager_added': '管理员已添加',
    'account.msg.manager_add_error': '添加管理员出错',
    'account.msg.manager_deleted': '管理员已删除',
    'account.msg.manager_delete_error': '删除管理员失败',
    'account.msg.manager_delete_self': '不能删除当前登录的管理员账号',
    'account.msg.manager_delete_last': '至少需要保留一个管理员账号'
  ,
    'assets.title': '固定资产',
    'assets.stats.by_category': '按类别统计',
    'assets.stats.by_status': '按状态统计',
    'assets.stats.none': '暂无数据',
    'assets.inbound.title': '入库单列表',
    'assets.inbound.add': '新建入库单',
    'assets.inbound.edit': '编辑入库单',
    'assets.inbound.order_number': '单据编号',
    'assets.inbound.supplier': '供货商',
    'assets.inbound.supplier_lead': '供货负责人',
    'assets.inbound.receiver_lead': '接货负责人',
    'assets.inbound.location': '入库地点',
    'assets.inbound.date': '入库日期',
    'assets.inbound.notes': '备注',
    'assets.inbound.assets_count': '资产数量',
    'assets.inbound.none': '暂无入库单',
    'assets.inbound.delete.title': '删除入库单',
    'assets.inbound.delete.message': '入库单 {order} 将同步删除 {count} 条绑定资产，请再次确认。',
    'assets.inbound.delete.confirm': '确定删除该入库单及全部绑定资产吗？',
    'assets.inbound.delete.double': '请再次确认：该入库单下的所有固定资产都会被删除。',
    'assets.list.title': '固定资产清单',
    'assets.list.mine': '我负责的资产',
    'assets.list.unassigned': '待分配或遗失资产',
    'assets.list.mine_empty': '目前没有分配给你的资产。',
    'assets.list.unassigned_empty': '暂无待分配或遗失的资产。',
    'assets.add': '新建固定资产',
    'assets.export': '导出Excel',
    'assets.sync_all.button': '同步全部',
    'assets.sync_all.title': '批量同步资产',
    'assets.sync_all.description': '请输入需要同步的资产编号，可使用逗号、分号或换行分隔，支持使用连字符表示范围（例如 100-120）。',
    'assets.sync_all.inbound_label': '入库单',
    'assets.sync_all.ids_label': '资产编号',
    'assets.sync_all.ids_placeholder': '示例：001-010,015,020',
    'assets.sync_all.ids_hint': '编号之间可用逗号、分号或换行分隔，使用连字符可自动补齐范围内的缺失编号。',
    'assets.sync_all.results_title': '同步进度',
    'assets.sync_all.start': '开始同步',
    'assets.sync_all.status.preparing': '正在准备同步列表…',
    'assets.sync_all.status.running': '正在依次同步资产…',
    'assets.sync_all.status.summary': '同步完成。成功 {success} 条，跳过 {skipped} 条，失败 {failed} 条。',
    'assets.sync_all.errors.inbound_required': '请选择入库单。',
    'assets.sync_all.errors.input_required': '请输入至少一个资产编号。',
    'assets.sync_all.result.success': '同步成功。',
    'assets.sync_all.result.updated': '已更新现有资产。',
    'assets.sync_all.result.skipped_exists': '已跳过：资产已存在。',
    'assets.sync_all.result.failed': '同步失败。',
    'assets.sync_all.row.pending': '待同步',
    'assets.sync_all.row.running': '同步中…',
    'assets.edit': '编辑固定资产',
    'assets.table.order_number': '入库单号',
    'assets.table.asset_code': '资产编号',
    'assets.table.category': '资产类别',
    'assets.table.model': '型号配置',
    'assets.table.organization': '所属单位',
    'assets.table.remarks': '备注',
    'assets.table.location': '当前地点',
    'assets.table.owner': '责任人',
    'assets.table.status': '状态',
    'assets.table.image': '照片',
    'assets.table.updated_at': '更新时间',
    'assets.table.actions': '操作',
    'assets.none': '暂无固定资产',
    'assets.action.edit': '编辑',
    'assets.action.goto': '跳转',
    'assets.action.delete': '删除',
    'assets.action.confirm_delete': '确认删除',
    'assets.form.inbound': '绑定入库单',
    'assets.form.inbound_placeholder': '选择入库单',
    'assets.form.asset_code': '固定资产编号',
    'assets.form.asset_code_suffix_placeholder': '留空自动生成',
    'assets.form.sync': '同步',
    'assets.form.sync_status.loading': '正在同步资产数据…',
    'assets.form.sync_status.success': '资产数据已同步并填充。',
    'assets.form.sync_status.error': '同步资产数据失败。',
    'assets.form.status': '资产状态',
    'assets.form.category': '资产类别',
    'assets.form.model': '型号配置',
    'assets.form.organization': '所属单位',
    'assets.form.remarks': '备注',
    'assets.form.office': '当前办公地点',
    'assets.form.seat': '工位',
    'assets.form.owner': '责任人',
    'assets.form.owner_other': '其他人',
    'assets.form.owner_other_placeholder': '请输入责任人姓名',
    'assets.form.owner_other_hint': '若责任人不在列表中，请手动输入姓名。',
    'assets.form.image': '资产照片',
    'assets.form.image_hint': '请上传资产照片以便核实。',
    'assets.form.image_remote': '同步图片预览',
    'assets.form.none': '无',
    'assets.form.reuse_prompt': '是否复用上一次填写的类别和型号配置？',
    'assets.form.office_option.region_label': '所属区域：{region}',
    'assets.form.office_option.location_label': '位置描述：{location}',
    'assets.form.office_option.main_separator': ' —— ',
    'assets.form.office_option.sub_separator': ' / ',
    'assets.logs.title': '操作历史',
    'assets.logs.empty': '暂无记录',
    'assets.status.in_use': '使用中',
    'assets.status.maintenance': '维修中',
    'assets.status.pending': '待分配',
    'assets.status.lost': '遗失',
    'assets.status.retired': '报废',
    'assets.save': '保存',
    'assets.cancel': '取消',
    'assets.delete.title': '删除固定资产',
    'assets.delete.message': '确定删除资产 {code} 吗？该操作无法撤销。',
    'assets.delete.confirm': '确定删除该固定资产吗？',
    'assets.messages.permission_denied': '您没有权限执行该操作。',
    'assets.messages.order_required': '必须填写单据编号。',
    'assets.messages.order_exists': '单据编号已存在。',
    'assets.messages.inbound_missing': '入库单不存在。',
    'assets.messages.inbound_created': '入库单新建成功。',
    'assets.messages.inbound_updated': '入库单更新成功。',
    'assets.messages.inbound_deleted': '入库单删除成功。',
    'assets.messages.asset_missing': '固定资产不存在。',
    'assets.messages.asset_created': '固定资产新建成功。',
    'assets.messages.asset_updated': '固定资产更新成功。',
    'assets.messages.owner_external_required': '请输入责任人姓名。',
    'assets.messages.asset_deleted': '固定资产删除成功。',
    'assets.messages.settings_saved': '通用配置更新成功。',
    'assets.messages.asset_code_exists': '固定资产编号已存在。',
    'assets.messages.invalid_seat': '所选工位不存在。',
    'assets.messages.seat_office_mismatch': '所选工位不属于当前办公地点。',
    'assets.messages.image_upload_failed': '资产照片上传失败。',
    'assets.messages.invalid_image': '上传文件不是有效的图片。',
    'assets.messages.generic_error': '操作失败，请稍后再试。',
    'assets.settings.title': '通用配置',
    'assets.settings.description': '配置固定资产模块的通用选项。',
    'assets.settings.code_prefix': '资产编号前缀',
    'assets.settings.code_prefix_hint': '该前缀会显示在资产编号输入框前，与后缀共同组成完整编号。',
    'assets.settings.link_prefix': '资产链接前缀',
    'assets.settings.link_prefix_hint': '与资产编号后缀拼接，用于跳转专业管理平台。',
    'assets.settings.sync_api_prefix': '同步接口前缀',
    'assets.settings.sync_api_prefix_hint': '与资产编号后缀拼接，用于访问对接的同步接口。',
    'assets.settings.save': '保存配置',
    'assets.settings.open_modal': '管理通用配置',
    'assets.sync.title': '同步接口',
    'assets.sync.description': '保存前缀后，可通过输入资产编号拉取接口返回的JSON，并为各字段建立对应关系。',
    'assets.sync.prefix_notice': '请先填写并保存同步接口前缀，随后再配置字段映射。',
    'assets.sync.sample_input_label': '测试资产编号',
    'assets.sync.sample_input_placeholder': '输入资产编号或编码',
    'assets.sync.sample_help': '系统会将资产编号去除前缀与前导零后拼接到同步接口前缀。',
    'assets.sync.load_button': '拉取样例',
    'assets.sync.sample_result_title': '样例响应',
    'assets.sync.sample_url': '请求链接：',
    'assets.sync.mapping_title': '字段对应关系',
    'assets.sync.mapping_description': '为需要同步的资产属性选择对应的JSON键。',
    'assets.sync.mapping.attribute': '资产属性',
    'assets.sync.mapping.json_key': 'JSON键',
    'assets.sync.mapping.none': '未关联',
    'assets.sync.save_button': '保存对应关系',
    'assets.sync.no_keys': '未在样例响应中解析到可用的键值。',
    'assets.sync.status.loading': '正在拉取样例数据…',
    'assets.sync.status.loaded': '样例数据已加载，请检查并更新字段对应。',
    'assets.sync.status.saving': '正在保存字段对应关系…',
    'assets.sync.status.saved': '字段对应关系保存成功。',
    'assets.sync.status.error': '操作失败，请重试。',
    'assets.sync.errors.prefix_missing': '请先配置同步接口前缀。',
    'assets.sync.errors.asset_required': '请输入测试用的资产编号。',
    'assets.sync.errors.asset_suffix_empty': '无法解析资产编号的后缀，请检查输入。',
    'assets.sync.errors.fetch_failed': '请求同步接口失败。',
    'assets.sync.errors.http': '同步接口返回了异常状态码。',
    'assets.sync.errors.invalid_json': '同步接口返回的内容不是有效的JSON。',
    'assets.sync.errors.mapping_missing': '请先完成字段映射配置。',
    'assets.sync.errors.unknown': '发生未知的同步错误。',
    'assets.sync.attributes.asset_code': '资产编号后缀',
    'assets.sync.attributes.category': '资产类别',
    'assets.sync.attributes.model': '型号配置',
    'assets.sync.attributes.organization': '所属单位',
    'assets.sync.attributes.remarks': '备注',
    'assets.sync.attributes.status': '状态',
    'assets.sync.attributes.owner': '责任人',
    'assets.sync.attributes.office': '办公地点描述',
    'assets.sync.attributes.seat': '工位描述',
    'assets.sync.attributes.image': '图片链接',
    'assets.assignments.title': '在岗成员资产列表',
    'assets.assignments.member': '成员',
    'assets.assignments.asset_code': '资产编号',
    'assets.assignments.organization': '所属单位',
    'assets.assignments.category': '资产类别',
    'assets.assignments.model': '型号配置',
    'assets.assignments.location': '所在地点',
    'assets.assignments.status': '资产状态',
    'assets.assignments.updated_at': '更新时间',
    'assets.assignments.none': '暂无在岗成员资产记录',
    'assets.assignments.member_empty': '暂无负责资产。',
    'assets.assignments.badge': '{code} - {category} - {model}',
    'assets.assignments.owner_external': '其他人员',
    'assets.assignments.owner_missing': '暂无责任人',
    'assets.assignments.other_title': '其他成员资产列表',
    'assets.assignments.other_none': '暂无需要核对的资产记录。'
  ,
  'reimburse.title': '报销批次',
  'reimburse.add_batch': '新增批次',
  'reimburse.table_title': '标题',
  'reimburse.table_deadline': '截止日期',
  'reimburse.table_incharge': '负责人',
  'reimburse.table_actions': '操作',
  'reimburse.action_details': '详情',
  'reimburse.action_download': '下载',
  'reimburse.action_edit': '编辑',
  'reimburse.batch.title': '标题',
  'reimburse.batch.incharge': '负责人',
  'reimburse.batch.deadline': '截止日期',
  'reimburse.batch.save': '保存',
  'reimburse.batch.cancel': '取消',
  'reimburse.batch.file': '发票文件',
  'reimburse.batch.amount': '金额',
  'reimburse.batch.upload': '上传',
  'reimburse.batch.autofill': '自动填写',
  'reimburse.batch.receipt': '发票',
  'reimburse.batch.uploader': '上传者',
  'reimburse.batch.actions': '操作',
  'reimburse.batch.delete': '删除',
  'reimburse.batch.confirm_delete': '删除该发票？',
  'reimburse.batch.confirm_delete_batch': '删除该批次？',
  'reimburse.batch.deadline_passed': '已过截止日期',
  'reimburse.batch.none': '无',
  'reimburse.table_myreceipts': '我的发票',
  'reimburse.batch.limit': '成员限额',
  'reimburse.batch.allowed_types': '允许类别',
  'reimburse.batch.category': '发票类别',
  'reimburse.batch.description': '简短描述',
  'reimburse.batch.price': '发票含税价',
  'reimburse.batch.upload_date': '上传日期',
  'reimburse.batch.status': '状态',
  'reimburse.category.office': '办公用品',
  'reimburse.category.electronic': '电子材料',
  'reimburse.category.membership': '会员注册',
  'reimburse.category.book': '图书',
  'reimburse.category.trip': '差旅',
  'reimburse.batch.edit': '编辑发票',
  'reimburse.batch.refuse': '拒绝',
  'reimburse.batch.confirm_refuse': '拒绝该发票？',
  'reimburse.refused.list': '不合规发票',
  'reimburse.refused.title': '不合规发票列表',
  'reimburse.refused.original_batch': '原批次',
  'reimburse.status.refused': '已拒绝',
  'reimburse.batch.batch': '报销批次',
  'reimburse.batch.file_required': '必须上传文件',
  'reimburse.batch.manager_no_upload': '管理员不可上传发票',
  'reimburse.batch.limit_exceed': '金额超过限额',
  'reimburse.batch.type_not_allowed': '类别不被允许',
  'reimburse.batch.prohibited': '发票包含禁用内容',
  'reimburse.batch.check_warning': '请仔细检查每张发票内容，并拒绝不合规发票，然后再进行下一步！',
  'reimburse.batch.notice.title': '发票批次说明',
  'reimburse.batch.notice.editable': '负责人或管理员可编辑',
  'reimburse.batch.notice.empty': '暂未填写批次说明。',
  'reimburse.batch.notice.edit_title': '编辑批次说明',
  'reimburse.batch.notice.edit_button': '编辑说明',
  'reimburse.batch.notice.en': '英文说明',
  'reimburse.batch.notice.zh': '中文说明',
  'reimburse.batch.notice.hint': '所有人可见，仅管理员或负责人可编辑。',
  'reimburse.batch.notice.cancel': '取消',
  'reimburse.batch.notice.save': '保存说明',
  'reimburse.status.submitted': '已提交',
  'reimburse.status.locked': '已锁定',
  'reimburse.status.complete': '已报销',
  'reimburse.status.open': '开放',
  'reimburse.status.completed': '已完成',
  'reimburse.batch.lock': '锁定批次',
  'reimburse.batch.complete': '完成报销',
  'reimburse.batch.unlock': '解锁批次',
  'reimburse.batch.reopen': '重新打开批次',
  'reimburse.batch.description_required': '必须填写描述',
  'reimburse.batch.total_member': '成员金额汇总',
  'reimburse.batch.total_category': '类别金额汇总',
  'reimburse.batch.logs': '修改日志',
  'reimburse.batch.campus_id': '学号',
  'reimburse.batch.total': '总金额',
  'reimburse.active.none': '当前无进行中的批次',
  'reimburse.completed.show': '展开已完成批次',
  'reimburse.completed.hide': '收起已完成批次',
  'reimburse.completed.title': '已完成批次',
  'reimburse.batch.logs.empty': '暂无修改记录',
  'reimburse.batch.logs.prev': '上一页',
  'reimburse.batch.logs.next': '下一页',
  'reimburse.batch.logs.page_label': '页码',
  'reimburse.keywords.manage': '禁用关键词',
  'reimburse.keywords.add': '添加',
  'reimburse.keywords.word': '关键词',
  'reimburse.announcement.title': '报销公告',
  'reimburse.announcement.label_en': '英文公告',
  'reimburse.announcement.label_zh': '中文公告',
  'reimburse.announcement.edit': '编辑公告',
  'reimburse.announcement.note_html': '可以使用HTML标签进行排版',

  'collect.title': '收集',
  'collect.add_template': '新增表单',
  'collect.edit_template': '编辑表单',
  'collect.name': '表单名称',
  'collect.description': '说明',
  'collect.status': '状态',
  'collect.status.open': '开放填写',
  'collect.status.paused': '暂停填写',
  'collect.status.ended': '结束填写',
  'collect.status.void': '作废',
  'collect.deadline': '截止日期',
  'collect.targets': '待填成员',
  'collect.member_selector.title': '在岗成员列表',
  'collect.member_selector.subtitle': '勾选需要填写的成员。',
  'collect.fields': '字段',
  'collect.field_label': '字段标题',
  'collect.field_type': '字段类型',
  'collect.field_required': '必填',
  'collect.field_options': '下拉选项（英文逗号分隔）',
  'collect.field_add': '新增字段',
  'collect.field_types.number': '数字',
  'collect.field_types.text': '文字',
  'collect.field_types.select': '下拉框',
  'collect.field_types.file': '文件',
  'collect.actions': '操作',
  'collect.save': '保存',
  'collect.cancel': '取消',
  'collect.template_card.assignees': '需填人数',
  'collect.template_card.submissions': '已提交',
  'collect.template_card.pending': '未提交',
  'collect.template_card.download': '打包下载',
  'collect.template_card.manage': '管理',
  'collect.template_card.fill': '填写表单',
  'collect.template_card.records': '我的记录',
  'collect.template_card.new_record': '新增记录',
  'collect.template_card.no_records': '暂无记录',
  'collect.template_card.members_pending': '未提交成员',
  'collect.template_card.members_done': '已提交成员',
  'collect.template_card.zip_label': '导出数据与附件',
  'collect.template_card.edit': '编辑',
  'collect.template_card.delete': '删除',
  'collect.template_card.status_label': '表单状态',
  'collect.template_card.access_limited': '仅管理员指定成员可填写。',
  'collect.template_card.status_hint': '仅开放状态可填写。',
  'collect.hide_archived': '收起结束/作废',
  'collect.show_archived': '展开结束/作废',
  'collect.submit_record': '提交',
  'collect.update_record': '更新',
  'collect.file_current': '当前文件',
  'collect.file_replace': '上传替换',
  'collect.member_only': '你没有填写权限',
  'collect.access_denied': '仅能填写分配给你的开放表单。',
  'collect.confirm_delete': '确认删除该表单？相关数据也会被移除。',
  'collect.download_zip': '打包下载',
  'collect.none': '暂无',
  'collect.record_created': '保存成功',
  'collect.record_updated': '更新成功',
  'collect.record_deleted': '删除成功',
  'collect.record_failed': '操作失败',
  'collect.delete_record': '删除',
  'collect.confirm_delete_record': '确认删除该条记录？该操作无法撤销。',
  'collect.toast_close': '关闭'
  }
};

let forceMobileNav = false;

if (typeof window !== 'undefined') {
  window.translations = translations;
}

function doubleConfirm(message) {
  return confirm(message) && confirm('Please confirm again to proceed.');
}

function copyText(text) {
  if (navigator.clipboard && window.isSecureContext) {
    return navigator.clipboard
      .writeText(text)
      .then(() => true)
      .catch(() => fallback());
  }
  return Promise.resolve(fallback());

  function fallback() {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.width = '1px';
    textarea.style.height = '1px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    let success = false;
    try {
      success = document.execCommand('copy');
    } catch (err) {
      success = false;
    }
    textarea.remove();
    return success;
  }
}

function debounce(fn, wait = 100) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn.apply(null, args), wait);
  };
}

function setupResponsiveNav(nav, onUpdate) {
  if (!nav) return () => {};
  const collapseElement = nav.closest('.navbar-collapse');
  const navbarContainer = nav.closest('.container-fluid') || nav.closest('.navbar');
  const navbarElement = nav.closest('.navbar');

  const isOverflowing = (element) => {
    if (!element) return false;
    return Math.ceil(element.scrollWidth) > Math.floor(element.clientWidth);
  };

  function adjustNav() {
    const isDesktop = window.matchMedia('(min-width: 992px)').matches;

    if (collapseElement?.classList.contains('show') && isDesktop) {
      if (!forceMobileNav) {
        forceMobileNav = true;
        updateMobileViewClass();
      }
      onUpdate?.();
      return;
    }

    if (!isDesktop) {
      if (forceMobileNav) {
        forceMobileNav = false;
      }
      updateMobileViewClass();
      onUpdate?.();
      return;
    }

    const wasForced = forceMobileNav;
    if (wasForced) {
      forceMobileNav = false;
      updateMobileViewClass();
    }

    let availableWidth = nav.clientWidth;
    if (collapseElement) {
      const previousDisplay = collapseElement.style.display;
      const previousVisibility = collapseElement.style.visibility;
      const previousPosition = collapseElement.style.position;
      const previousPointerEvents = collapseElement.style.pointerEvents;
      const previousWidth = collapseElement.style.width;

      const computed = window.getComputedStyle(collapseElement);
      if (computed.display === 'none') {
        collapseElement.style.display = 'block';
        collapseElement.style.visibility = 'hidden';
        collapseElement.style.position = 'absolute';
        collapseElement.style.pointerEvents = 'none';
        collapseElement.style.width = '100%';
      }

      availableWidth = collapseElement.clientWidth || collapseElement.getBoundingClientRect().width;

      collapseElement.style.display = previousDisplay;
      collapseElement.style.visibility = previousVisibility;
      collapseElement.style.position = previousPosition;
      collapseElement.style.pointerEvents = previousPointerEvents;
      collapseElement.style.width = previousWidth;
    }

    const containerOverflowing = isOverflowing(navbarContainer);
    const navbarOverflowing = navbarElement && navbarElement !== navbarContainer
      ? isOverflowing(navbarElement)
      : false;
    const navOverflowing = Math.ceil(nav.scrollWidth) > Math.floor(availableWidth);
    const shouldForceMobile = navOverflowing || containerOverflowing || navbarOverflowing;

    if (shouldForceMobile !== forceMobileNav) {
      forceMobileNav = shouldForceMobile;
      updateMobileViewClass();
    } else if (shouldForceMobile && !wasForced) {
      updateMobileViewClass();
    }

    onUpdate?.();
  }

  const debouncedAdjustNav = debounce(adjustNav, 150);
  window.addEventListener('resize', debouncedAdjustNav);

  const resizeObserver = typeof ResizeObserver !== 'undefined'
    ? new ResizeObserver(() => debouncedAdjustNav())
    : null;

  resizeObserver?.observe(nav);
  navbarContainer && resizeObserver?.observe(navbarContainer);
  if (navbarElement && navbarElement !== navbarContainer) {
    resizeObserver?.observe(navbarElement);
  }

  if (collapseElement) {
    collapseElement.addEventListener('hidden.bs.collapse', () => {
      debouncedAdjustNav();
    });
  }

  adjustNav();
  return adjustNav;
}

function isMobileDevice() {
  const ua = navigator.userAgent || '';
  return /Mobi|Android|iPhone|iPad|iPod/i.test(ua);
}

function shouldUseMobileLayout() {
  return forceMobileNav || isMobileDevice() || window.innerWidth <= 768;
}

function updateMobileViewClass() {
  const body = document.body;
  if (!body) return;
  const useMobile = shouldUseMobileLayout();
  if (useMobile) {
    body.classList.add('mobile-view');
  } else {
    body.classList.remove('mobile-view');
  }

  if (!useMobile) {
    const collapseElement = document.getElementById('navbarNav');
    collapseElement?.classList.remove('show');
  }
}

function applyTranslations() {
  const lang = localStorage.getItem('lang') || 'zh';
  document.documentElement.lang = lang;
  document.querySelectorAll('[data-i18n-attr]').forEach(el => {
    const mapping = el.getAttribute('data-i18n-attr');
    if (!mapping) return;
    mapping.split(',').forEach(pair => {
      const [attr, key] = pair.split(':').map(part => part.trim());
      if (!attr || !key) return;
      const text = translations[lang][key];
      if (typeof text === 'string') {
        el.setAttribute(attr, text);
      }
    });
  });
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    let text = translations[lang][key];
    if (text) {
      const paramsAttr = el.getAttribute('data-i18n-params');
      if (paramsAttr) {
        try {
          const params = JSON.parse(paramsAttr);
          Object.keys(params).forEach(paramKey => {
            const value = params[paramKey];
            const pattern = new RegExp(`\\{${paramKey}\\}`, 'g');
            text = text.replace(pattern, value);
          });
        } catch (err) {
          console.error('Invalid translation params', err);
        }
      }
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
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    const key = el.getAttribute('data-i18n-placeholder');
    const text = translations[lang][key];
    if(text) {
      el.setAttribute('placeholder', text);
    }
  });
  document.querySelectorAll('[data-extra-name-zh]').forEach(el => {
    const zhName = el.getAttribute('data-extra-name-zh') || '';
    const enName = el.getAttribute('data-extra-name-en') || '';
    const fallback = typeof el.textContent === 'string' ? el.textContent : '';
    const text = lang === 'en'
      ? (enName || zhName || fallback)
      : (zhName || enName || fallback);
    if (typeof el.textContent === 'string') {
      el.textContent = text;
    }
  });
  const officeRegionTemplate = translations[lang]['assets.form.office_option.region_label'] || '{region}';
  const officeLocationTemplate = translations[lang]['assets.form.office_option.location_label'] || '{location}';
  const officeMainSeparator = Object.prototype.hasOwnProperty.call(translations[lang], 'assets.form.office_option.main_separator')
    ? translations[lang]['assets.form.office_option.main_separator']
    : ' — ';
  const officeSubSeparator = Object.prototype.hasOwnProperty.call(translations[lang], 'assets.form.office_option.sub_separator')
    ? translations[lang]['assets.form.office_option.sub_separator']
    : ' / ';
  document.querySelectorAll('[data-office-option]').forEach(option => {
    const name = option.getAttribute('data-office-name') || '';
    if (!name) {
      return;
    }
    const region = option.getAttribute('data-office-region') || '';
    const location = option.getAttribute('data-office-location') || '';
    const segments = [];
    if (region) {
      segments.push(officeRegionTemplate.replace('{region}', region));
    }
    if (location) {
      segments.push(officeLocationTemplate.replace('{location}', location));
    }
    const detail = segments.join(officeSubSeparator);
    option.textContent = detail ? `${name}${officeMainSeparator}${detail}` : name;
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
  const exportLink = document.getElementById('exportMembers');
  if(exportLink) {
    exportLink.href = `members_export.php?lang=${lang}`;
  }
  const exportAssetsLink = document.getElementById('exportAssets');
  if (exportAssetsLink) {
    exportAssetsLink.href = `assets_export.php?lang=${lang}`;
  }
}

function clampChannel(value) {
  return Math.max(0, Math.min(255, Math.round(value)));
}

function parseColorValue(value) {
  if (!value) return null;
  let color = value.trim();
  const hexMatch = color.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hexMatch) {
    let hex = hexMatch[1];
    if (hex.length === 3) {
      hex = hex.split('').map(ch => ch + ch).join('');
    }
    const num = parseInt(hex, 16);
    return {
      r: (num >> 16) & 255,
      g: (num >> 8) & 255,
      b: num & 255,
    };
  }
  const rgbMatch = color.match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i);
  if (rgbMatch) {
    return {
      r: clampChannel(parseInt(rgbMatch[1], 10)),
      g: clampChannel(parseInt(rgbMatch[2], 10)),
      b: clampChannel(parseInt(rgbMatch[3], 10)),
    };
  }
  return null;
}

function rgbToCss({ r, g, b }) {
  return `rgb(${clampChannel(r)}, ${clampChannel(g)}, ${clampChannel(b)})`;
}

function rgbaToCss({ r, g, b }, alpha) {
  const a = Math.max(0, Math.min(1, alpha));
  return `rgba(${clampChannel(r)}, ${clampChannel(g)}, ${clampChannel(b)}, ${a})`;
}

function rgbToHsl({ r, g, b }) {
  const nr = clampChannel(r) / 255;
  const ng = clampChannel(g) / 255;
  const nb = clampChannel(b) / 255;
  const max = Math.max(nr, ng, nb);
  const min = Math.min(nr, ng, nb);
  let h = 0;
  let s = 0;
  const l = (max + min) / 2;

  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case nr:
        h = (ng - nb) / d + (ng < nb ? 6 : 0);
        break;
      case ng:
        h = (nb - nr) / d + 2;
        break;
      default:
        h = (nr - ng) / d + 4;
        break;
    }
    h /= 6;
  }

  return { h, s, l };
}

function hslToRgb({ h, s, l }) {
  let r;
  let g;
  let b;

  if (s === 0) {
    r = g = b = l;
  } else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return {
    r: Math.round(r * 255),
    g: Math.round(g * 255),
    b: Math.round(b * 255),
  };
}

function createDarkVariant(rgb) {
  const hsl = rgbToHsl(rgb);
  const adjustedLightness = Math.max(0.18, Math.min(0.55, hsl.l * 0.45 + 0.08));
  const adjustedSaturation = Math.min(1, hsl.s * 0.9 + 0.05);
  return hslToRgb({ h: hsl.h, s: adjustedSaturation, l: adjustedLightness });
}

function relativeLuminance({ r, g, b }) {
  const channel = value => {
    const normalized = clampChannel(value) / 255;
    return normalized <= 0.03928
      ? normalized / 12.92
      : Math.pow((normalized + 0.055) / 1.055, 2.4);
  };
  return (
    0.2126 * channel(r) +
    0.7152 * channel(g) +
    0.0722 * channel(b)
  );
}

function pickTextRgb(bgRgb) {
  const lum = relativeLuminance(bgRgb);
  if (lum > 0.55) {
    return { r: 30, g: 41, b: 59 };
  }
  return { r: 241, g: 245, b: 249 };
}

function updateCustomRowColors(theme) {
  const rows = document.querySelectorAll('tr[data-custom-bg]');
  rows.forEach(row => {
    const baseColor = parseColorValue(row.dataset.customBg);
    if (!baseColor) {
      row.style.removeProperty('--custom-row-text-color');
      row.style.removeProperty('--custom-row-muted-color');
      return;
    }

    const appliedColor = theme === 'dark' ? createDarkVariant(baseColor) : baseColor;
    const textRgb = pickTextRgb(appliedColor);
    const mutedAlpha = theme === 'dark' ? 0.78 : 0.65;

    row.style.backgroundColor = rgbToCss(appliedColor);
    row.style.setProperty('--custom-row-text-color', rgbToCss(textRgb));
    row.style.setProperty('--custom-row-muted-color', rgbaToCss(textRgb, mutedAlpha));
  });
}

function applyTheme() {
  const theme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-bs-theme', theme);
  document.body.classList.toggle('theme-dark', theme === 'dark');
  document.body.classList.toggle('theme-light', theme !== 'dark');
  updateCustomRowColors(theme);
}

function initApp() {
  applyTheme();
  applyTranslations();

  let refreshNavOverflow = null;

  updateMobileViewClass();
  const debouncedMobileUpdate = debounce(() => {
    updateMobileViewClass();
    refreshNavOverflow?.();
  }, 150);
  window.addEventListener('resize', debouncedMobileUpdate);

  const langBtn = document.getElementById('langToggle');

  if (langBtn) {
    langBtn.addEventListener('click', () => {
      const current = localStorage.getItem('lang') || 'zh';
      const next = current === 'en' ? 'zh' : 'en';
      localStorage.setItem('lang', next);
      applyTranslations();
      refreshNavOverflow?.();
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
      refreshNavOverflow?.();
    });
  }

  const exportAssessmentBtn = document.getElementById('exportAssessment');
  const assessmentForm = document.getElementById('assessmentFilterForm');
  if (exportAssessmentBtn && assessmentForm) {
    exportAssessmentBtn.addEventListener('click', () => {
      const lang = localStorage.getItem('lang') === 'en' ? 'en' : 'zh';
      const dict = translations[lang] || translations.zh;
      const defaultLabel = dict['todolist.assessment.export_txt'] || exportAssessmentBtn.textContent || 'Export TXT';
      const exportingLabel = dict['todolist.assessment.exporting'] || 'Exporting…';
      const missingRangeLabel = dict['todolist.assessment.export_missing_range'] || 'Please select both start and end dates before exporting.';
      const errorLabel = dict['todolist.assessment.export_error'] || 'Export failed, please try again.';
      const formData = new FormData(assessmentForm);
      const start = (formData.get('start') || '').toString().trim();
      const end = (formData.get('end') || '').toString().trim();
      if (!start || !end) {
        alert(missingRangeLabel);
        return;
      }

      const params = new URLSearchParams();
      formData.forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') {
          params.set(key, value.trim());
        }
      });
      params.set('export', 'txt');
      params.set('lang', lang);

      const downloadUrl = `todolist_assessment.php?${params.toString()}`;
      exportAssessmentBtn.disabled = true;
      exportAssessmentBtn.textContent = exportingLabel;

      const restoreButton = () => {
        exportAssessmentBtn.disabled = false;
        exportAssessmentBtn.textContent = defaultLabel;
      };

      const fallbackDownload = () => {
        if (typeof window.open === 'function') {
          const newWindow = window.open(downloadUrl, '_blank', 'noopener=yes');
          if (newWindow) {
            try {
              newWindow.opener = null;
            } catch (err) {
              // ignore inability to clear opener
            }
            return true;
          }
        }
        if (typeof window.location !== 'undefined') {
          window.location.href = downloadUrl;
          return true;
        }
        return false;
      };

      const triggerDownload = (blob, filename) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        setTimeout(() => {
          URL.revokeObjectURL(url);
          link.remove();
        }, 1000);
      };

      const parseFilenameFromDisposition = disposition => {
        if (!disposition) return null;
        const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8Match && utf8Match[1]) {
          try {
            return decodeURIComponent(utf8Match[1]);
          } catch (err) {
            return utf8Match[1];
          }
        }
        const quotedMatch = disposition.match(/filename="?([^";]+)"?/i);
        return quotedMatch && quotedMatch[1] ? quotedMatch[1] : null;
      };

      if (window.fetch) {
        fetch(downloadUrl, { credentials: 'same-origin' })
          .then(response => {
            if (!response.ok) {
              throw new Error('network');
            }
            const disposition = response.headers.get('Content-Disposition');
            return response.blob().then(blob => ({ blob, disposition }));
          })
          .then(({ blob, disposition }) => {
            const fallbackName = `todolist_${start}_${end}.txt`;
            const filename = parseFilenameFromDisposition(disposition) || fallbackName;
            triggerDownload(blob, filename);
            setTimeout(restoreButton, 200);
          })
          .catch(() => {
            const fallbackSucceeded = fallbackDownload();
            if (!fallbackSucceeded) {
              exportAssessmentBtn.disabled = false;
              exportAssessmentBtn.textContent = errorLabel;
              alert(errorLabel);
              setTimeout(restoreButton, 1600);
            } else {
              setTimeout(restoreButton, 1200);
            }
          });
      } else {
        const fallbackSucceeded = fallbackDownload();
        if (!fallbackSucceeded) {
          exportAssessmentBtn.disabled = false;
          exportAssessmentBtn.textContent = errorLabel;
          alert(errorLabel);
          setTimeout(restoreButton, 1600);
        } else {
          setTimeout(restoreButton, 1200);
        }
      }
    });
  }

  const promptCopyButtons = document.querySelectorAll('.copy-prompt');
  if (promptCopyButtons.length) {
    promptCopyButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const target = targetId ? document.getElementById(targetId) : null;
        if (!target) return;
        const text = target.textContent?.trim();
        if (!text) return;
        const lang = localStorage.getItem('lang') || 'zh';
        const dict = translations[lang] || translations.zh;
        const copyLabel = dict['todolist.assessment.prompts.copy'] || btn.textContent || 'Copy';
        const copiedLabel = dict['todolist.assessment.prompts.copied'] || 'Copied!';
        const errorLabel = dict['todolist.assessment.prompts.copy_error'] || 'Copy failed, please copy manually.';
        btn.disabled = true;
        copyText(text)
          .then(success => {
            if (success) {
              btn.textContent = copiedLabel;
            } else {
              btn.textContent = errorLabel;
              alert(errorLabel);
            }
          })
          .catch(() => {
            btn.textContent = errorLabel;
            alert(errorLabel);
          })
          .finally(() => {
            setTimeout(() => {
              btn.textContent = copyLabel;
              btn.disabled = false;
            }, 2000);
          });
      });
    });
  }

  const qrLinkInput = document.getElementById('qrLinkInput');
  const qrCopyBtn = document.getElementById('qrCopyBtn');
  const qrLinkAnchor = document.getElementById('qrLinkAnchor');
  const qrButtons = document.querySelectorAll('.qr-btn');
  if (qrCopyBtn && qrLinkInput) {
    qrCopyBtn.addEventListener('click', () => {
      qrLinkInput.select();
      copyText(qrLinkInput.value);
    });
  }
  qrButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const url = btn.dataset.url;
      const fullUrl = new URL(url, window.location.href).href;
      const img = document.getElementById('qrImage');
      if (img) {
        img.src =
          'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' +
          encodeURIComponent(fullUrl);
      }
      if (qrLinkInput) {
        qrLinkInput.value = fullUrl;
      }
      if (qrLinkAnchor) {
        qrLinkAnchor.href = fullUrl;
        qrLinkAnchor.textContent = fullUrl;
      }
      const modal = new bootstrap.Modal(document.getElementById('qrModal'));
      modal.show();
    });
  });

  const nav = document.querySelector('.navbar-nav');
  if(nav){
    refreshNavOverflow = setupResponsiveNav(nav);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
