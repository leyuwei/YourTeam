import Foundation

struct AuthService {
    func login(serverAddress: String, username: String, password: String, role: UserRole) async throws -> AuthResult {
        let trimmedServer = serverAddress.trimmingCharacters(in: .whitespacesAndNewlines)
        guard let url = URL(string: trimmedServer), url.scheme != nil else {
            throw AuthError.invalidServer
        }

        guard !username.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty,
              !password.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else {
            throw AuthError.missingCredentials
        }

        try await Task.sleep(nanoseconds: 450_000_000)
        return AuthResult(username: username, role: role)
    }
}

struct AuthResult {
    let username: String
    let role: UserRole
}

enum AuthError: LocalizedError {
    case invalidServer
    case missingCredentials

    var errorDescription: String? {
        switch self {
        case .invalidServer:
            return NSLocalizedString("error.invalid_server", comment: "")
        case .missingCredentials:
            return NSLocalizedString("error.missing_credentials", comment: "")
        }
    }
}
