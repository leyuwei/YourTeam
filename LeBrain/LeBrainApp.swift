import SwiftUI

@main
struct LeBrainApp: App {
    @StateObject private var appState = AppState()
    @StateObject private var serverStore = ServerStore()
    @StateObject private var todoStore = TodoStore()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(appState)
                .environmentObject(serverStore)
                .environmentObject(todoStore)
        }
    }
}
