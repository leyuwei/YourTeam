import Combine
import Foundation

final class ServerStore: ObservableObject {
    @Published var serverAddress: String {
        didSet {
            UserDefaults.standard.set(serverAddress, forKey: storageKey)
        }
    }

    private let storageKey = "lebrain.serverAddress"

    init() {
        self.serverAddress = UserDefaults.standard.string(forKey: storageKey) ?? "https://"
    }
}
