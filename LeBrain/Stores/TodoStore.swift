import Combine
import Foundation

final class TodoStore: ObservableObject {
    @Published private(set) var items: [TodoItem] = []

    init() {
        items = [
            TodoItem(title: NSLocalizedString("sample.todo.team", comment: ""), detail: NSLocalizedString("sample.todo.team.detail", comment: "")),
            TodoItem(title: NSLocalizedString("sample.todo.personal", comment: ""), detail: NSLocalizedString("sample.todo.personal.detail", comment: ""))
        ]
    }

    func add(title: String, detail: String) {
        let item = TodoItem(title: title, detail: detail)
        items.insert(item, at: 0)
    }

    func update(item: TodoItem, title: String, detail: String) {
        guard let index = items.firstIndex(where: { $0.id == item.id }) else { return }
        items[index].title = title
        items[index].detail = detail
    }

    func toggleCompletion(for item: TodoItem) {
        guard let index = items.firstIndex(where: { $0.id == item.id }) else { return }
        items[index].isCompleted.toggle()
    }

    func delete(at offsets: IndexSet) {
        items.remove(atOffsets: offsets)
    }
}
