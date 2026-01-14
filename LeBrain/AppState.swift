import Foundation

final class AppState: ObservableObject {
    @Published var isLoggedIn = false
    @Published var currentRole: UserRole = .member
    @Published var currentUsername = ""

    func logout() {
        isLoggedIn = false
        currentUsername = ""
        currentRole = .member
    }
}
