import SwiftUI

struct SettingsView: View {
    @EnvironmentObject private var serverStore: ServerStore
    @Environment(\.dismiss) private var dismiss

    @State private var draftServerAddress = ""

    var body: some View {
        NavigationStack {
            Form {
                Section {
                    TextField(String(localized: "server.address.placeholder"), text: $draftServerAddress)
                        .textInputAutocapitalization(.never)
                        .disableAutocorrection(true)
                } header: {
                    Text("server.address")
                } footer: {
                    Text("server.address.helper")
                }
            }
            .navigationTitle("settings.title")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("action.cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("action.save") {
                        serverStore.serverAddress = draftServerAddress
                        dismiss()
                    }
                    .disabled(draftServerAddress.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            }
            .onAppear {
                draftServerAddress = serverStore.serverAddress
            }
        }
    }
}
