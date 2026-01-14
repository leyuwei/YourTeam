import Foundation

struct TodoItem: Identifiable, Hashable {
    let id: UUID
    var title: String
    var detail: String
    var isCompleted: Bool
    var createdAt: Date

    init(id: UUID = UUID(), title: String, detail: String = "", isCompleted: Bool = false, createdAt: Date = Date()) {
        self.id = id
        self.title = title
        self.detail = detail
        self.isCompleted = isCompleted
        self.createdAt = createdAt
    }
}
