import Foundation

enum UserRole: String, CaseIterable, Identifiable {
    case admin
    case member

    var id: String { rawValue }

    var localizedTitleKey: String {
        switch self {
        case .admin:
            return "role.admin"
        case .member:
            return "role.member"
        }
    }
}
