import SwiftUI

struct TodoListView: View {
    @EnvironmentObject private var appState: AppState
    @EnvironmentObject private var todoStore: TodoStore

    @State private var showEditor = false
    @State private var editingItem: TodoItem?

    var body: some View {
        NavigationStack {
            Group {
                if todoStore.items.isEmpty {
                    EmptyStateView(titleKey: "todo.empty.title", messageKey: "todo.empty.message")
                } else {
                    List {
                        ForEach(todoStore.items) { item in
                            TodoRow(item: item) {
                                todoStore.toggleCompletion(for: item)
                            }
                            .contentShape(Rectangle())
                            .onTapGesture {
                                editingItem = item
                                showEditor = true
                            }
                        }
                        .onDelete(perform: todoStore.delete)
                    }
                }
            }
            .navigationTitle("todo.title")
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Menu {
                        Button("action.logout", role: .destructive) {
                            appState.logout()
                        }
                    } label: {
                        Label("account", systemImage: "person.crop.circle")
                    }
                }

                ToolbarItem(placement: .navigationBarTrailing) {
                    Button {
                        editingItem = nil
                        showEditor = true
                    } label: {
                        Label("action.add", systemImage: "plus")
                    }
                }
            }
            .sheet(isPresented: $showEditor) {
                TodoEditorView(item: editingItem)
            }
        }
    }
}
