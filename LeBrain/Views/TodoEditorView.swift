import SwiftUI

struct TodoEditorView: View {
    @EnvironmentObject private var todoStore: TodoStore
    @Environment(
        \.dismiss
    ) private var dismiss

    let item: TodoItem?

    @State private var title: String
    @State private var detail: String

    init(item: TodoItem?) {
        self.item = item
        _title = State(initialValue: item?.title ?? "")
        _detail = State(initialValue: item?.detail ?? "")
    }

    var body: some View {
        NavigationStack {
            Form {
                Section(header: Text("todo.form.title")) {
                    TextField(String(localized: "todo.form.title.placeholder"), text: $title)
                }

                Section(header: Text("todo.form.detail")) {
                    TextField(String(localized: "todo.form.detail.placeholder"), text: $detail, axis: .vertical)
                        .lineLimit(3...6)
                }
            }
            .navigationTitle(item == nil ? "todo.add" : "todo.edit")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("action.cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("action.save") {
                        save()
                        dismiss()
                    }
                    .disabled(title.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
                }
            }
        }
    }

    private func save() {
        let trimmedTitle = title.trimmingCharacters(in: .whitespacesAndNewlines)
        let trimmedDetail = detail.trimmingCharacters(in: .whitespacesAndNewlines)
        if let item {
            todoStore.update(item: item, title: trimmedTitle, detail: trimmedDetail)
        } else {
            todoStore.add(title: trimmedTitle, detail: trimmedDetail)
        }
    }
}
