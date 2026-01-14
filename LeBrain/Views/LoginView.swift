import SwiftUI

struct LoginView: View {
    @EnvironmentObject private var appState: AppState
    @EnvironmentObject private var serverStore: ServerStore

    @State private var username = ""
    @State private var password = ""
    @State private var selectedRole: UserRole = .member
    @State private var isLoading = false
    @State private var alertMessage: String?
    @State private var showSettings = false

    private let authService = AuthService()

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    VStack(alignment: .leading, spacing: 8) {
                        Text("login.title")
                            .font(.largeTitle.bold())
                        Text("login.subtitle")
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                    }

                    VStack(alignment: .leading, spacing: 12) {
                        Text("server.address")
                            .font(.headline)
                        HStack(spacing: 8) {
                            Text(serverStore.serverAddress)
                                .font(.subheadline)
                                .foregroundColor(.secondary)
                                .lineLimit(1)
                            Spacer()
                            Button("action.configure") {
                                showSettings = true
                            }
                        }
                        .padding(12)
                        .background(Color(.secondarySystemBackground))
                        .clipShape(RoundedRectangle(cornerRadius: 12))
                    }

                    VStack(alignment: .leading, spacing: 12) {
                        Text("login.role")
                            .font(.headline)
                        Picker("login.role", selection: $selectedRole) {
                            ForEach(UserRole.allCases) { role in
                                Text(role.localizedTitleKey).tag(role)
                            }
                        }
                        .pickerStyle(.segmented)
                    }

                    VStack(alignment: .leading, spacing: 12) {
                        Text("login.account")
                            .font(.headline)
                        TextField(String(localized: "login.username"), text: $username)
                            .textInputAutocapitalization(.never)
                            .disableAutocorrection(true)
                            .textFieldStyle(.roundedBorder)
                        SecureField(String(localized: "login.password"), text: $password)
                            .textFieldStyle(.roundedBorder)
                    }

                    Button {
                        Task { await handleLogin() }
                    } label: {
                        HStack {
                            Spacer()
                            if isLoading {
                                ProgressView()
                            } else {
                                Text("action.login")
                            }
                            Spacer()
                        }
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(isLoading)

                    VStack(alignment: .leading, spacing: 8) {
                        Text("login.helper.title")
                            .font(.headline)
                        Text("login.helper.body")
                            .font(.footnote)
                            .foregroundColor(.secondary)
                    }
                }
                .padding(24)
            }
            .navigationTitle("app.name")
            .alert(item: Binding(get: {
                alertMessage.map { AlertMessage(text: $0) }
            }, set: { _ in
                alertMessage = nil
            })) { message in
                Alert(title: Text("alert.title"), message: Text(message.text), dismissButton: .default(Text("action.ok")))
            }
            .sheet(isPresented: $showSettings) {
                SettingsView()
            }
        }
    }

    private func handleLogin() async {
        isLoading = true
        defer { isLoading = false }

        do {
            let result = try await authService.login(
                serverAddress: serverStore.serverAddress,
                username: username,
                password: password,
                role: selectedRole
            )
            appState.currentUsername = result.username
            appState.currentRole = result.role
            appState.isLoggedIn = true
        } catch {
            alertMessage = error.localizedDescription
        }
    }
}

private struct AlertMessage: Identifiable {
    let id = UUID()
    let text: String
}
