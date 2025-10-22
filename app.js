const translations = {
  en: {
    'nav.home': 'Team Management',
    'nav.members': 'Members',
    'nav.todolist': 'Todolist',
    'nav.projects': 'Projects',
    'nav.directions': 'Research',
    'nav.offices': 'Offices',
    'nav.notifications': 'Notifications',
    'nav.reimburse': 'Reimbursement',
    'nav.assets': 'Assets',
    'nav.tasks': 'Tasks',
    'nav.workload': 'Workload',
    'nav.account': 'Account',
    'welcome': 'Welcome',
    'logout': 'Logout',
    'header.title': 'Team Management Platform',
    'qr.scan': 'Scan to Enter',
    'qr.copy': 'Copy Link',
    'login.title': 'Member Login',
    'login.title.manager': 'Manager Login',
    'login.title.member': 'Member Login',
    'login.username': 'Username',
    'login.password': 'Password',
    'login.button': 'Login',
    'login.radio.manager': 'Manager',
    'login.radio.member': 'Member',
    'login.warning.manager': 'You are logging in as a manager.',
    'login.warning.member': 'You are logging in as a normal member.',
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
  'members.extra.field.default_value': 'Default Value',
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
    'todolist.assessment': 'Assessment',
    'todolist.assessment.generate': 'Generate',
    'todolist.assessment.no_items': 'No todo items',
    'todolist.copy_next': 'Cut to Next Week',
    'todolist.copy_item': 'Copy',
    'todolist.status.pending': 'Saving…',
    'todolist.status.success': 'Saved automatically',
    'todolist.status.error': 'Save failed, please try again',
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
    'account.msg.manager_add_error': 'Error adding manager'
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
    'assets.add': 'New Asset',
    'assets.export': 'Export to Excel',
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
    'assets.form.status': 'Status',
    'assets.form.category': 'Category',
    'assets.form.model': 'Model / Configuration',
    'assets.form.organization': 'Owning Unit',
    'assets.form.remarks': 'Remarks',
    'assets.form.office': 'Current Office',
    'assets.form.seat': 'Workstation',
    'assets.form.owner': 'Person in Charge',
    'assets.form.image': 'Asset Photo',
    'assets.form.image_hint': 'Upload an asset photo for verification.',
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
    'assets.settings.save': 'Save Settings',
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
    'assets.assignments.member_empty': 'No assets assigned.'
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
  'reimburse.keywords.manage': 'Prohibited Keywords',
  'reimburse.keywords.add': 'Add',
  'reimburse.keywords.word': 'Keyword',
  'reimburse.announcement.title': 'Reimbursement Announcement',
  'reimburse.announcement.label_en': 'English Announcement',
  'reimburse.announcement.label_zh': 'Chinese Announcement',
  'reimburse.announcement.edit': 'Edit Announcement',
  'reimburse.announcement.note_html': 'You can use HTML tags for styling.'
  },
  zh: {
    'nav.home': '团队管理',
    'nav.members': '成员列表',
    'nav.todolist': '待办事项',
    'nav.projects': '横纵项目',
    'nav.directions': '研究方向',
    'nav.offices': '办公地点',
    'nav.notifications': '定向通知',
    'nav.reimburse': '财务报销',
    'nav.assets': '固定资产',
    'nav.tasks': '任务指派',
    'nav.workload': '工作量统计',
    'nav.account': '管理账户',
    'welcome': '欢迎',
    'logout': '退出登录',
    'header.title': '团队管理平台',
    'qr.scan': '扫码进入',
    'qr.copy': '复制链接',
    'login.title': '成员登录',
    'login.title.manager': '管理员登录',
    'login.title.member': '成员登录',
    'login.username': '用户名',
    'login.password': '密码',
    'login.button': '登录',
    'login.radio.manager': '管理员',
    'login.radio.member': '一般成员',
    'login.warning.manager': '您正在以管理员身份登录。',
    'login.warning.member': '您正在以普通成员身份登录。',
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
  'members.extra.field.default_value': '默认值',
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
    'todolist.assessment': '待办统计',
    'todolist.assessment.generate': '统计',
    'todolist.assessment.no_items': '无待办事项',
    'todolist.copy_next': '鸽下周',
    'todolist.copy_item': '复制',
    'todolist.status.pending': '保存中…',
    'todolist.status.success': '已自动保存',
    'todolist.status.error': '保存失败，请稍后重试',
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
    'account.msg.manager_add_error': '添加管理员出错'
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
    'assets.add': '新建固定资产',
    'assets.export': '导出Excel',
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
    'assets.form.status': '资产状态',
    'assets.form.category': '资产类别',
    'assets.form.model': '型号配置',
    'assets.form.organization': '所属单位',
    'assets.form.remarks': '备注',
    'assets.form.office': '当前办公地点',
    'assets.form.seat': '工位',
    'assets.form.owner': '责任人',
    'assets.form.image': '资产照片',
    'assets.form.image_hint': '请上传资产照片以便核实。',
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
    'assets.settings.save': '保存配置',
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
    'assets.assignments.member_empty': '暂无负责资产。'
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
  'reimburse.keywords.manage': '禁用关键词',
  'reimburse.keywords.add': '添加',
  'reimburse.keywords.word': '关键词',
  'reimburse.announcement.title': '报销公告',
  'reimburse.announcement.label_en': '英文公告',
  'reimburse.announcement.label_zh': '中文公告',
  'reimburse.announcement.edit': '编辑公告',
  'reimburse.announcement.note_html': '可以使用HTML标签进行排版'
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
    navigator.clipboard.writeText(text).catch(fallback);
  } else {
    fallback();
  }
  function fallback() {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      document.execCommand('copy');
    } finally {
      textarea.remove();
    }
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

  function adjustNav() {
    const isDesktop = window.matchMedia('(min-width: 992px)').matches;

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

    const isOverflowing = nav.scrollWidth > availableWidth;
    if (isOverflowing !== forceMobileNav) {
      forceMobileNav = isOverflowing;
      updateMobileViewClass();
    } else if (isOverflowing && !wasForced) {
      updateMobileViewClass();
    }

    onUpdate?.();
  }

  const debouncedAdjustNav = debounce(adjustNav, 150);
  window.addEventListener('resize', debouncedAdjustNav);
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
