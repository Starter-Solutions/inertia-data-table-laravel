# starter-solutions/inertia-data-table

> ⚠️ This is the **Laravel backend package** for  
> **@starter-solutions/inertia-data-table-vue (Vue 3 + Inertia companion package)**.
>
> If you are looking for the frontend package, visit:  
> 👉 https://github.com/starter-solutions/inertia-data-table-vue

---

## 📦 Overview

`starter-solutions/inertia-data-table` provides a clean and consistent way to handle:

- Server-driven pagination
- Sorting
- Query parameter management
- Multiple independent tables per page
- Inertia-powered table state synchronization

It extends Laravel’s query builder with a data-table macro that integrates seamlessly with Inertia.js and Vue 3.

This package is designed to work together with:

**Frontend (Vue 3 + Inertia):**  
https://github.com/starter-solutions/inertia-data-table-vue

Together they provide a structured, reusable approach to building sortable and paginated data tables in Laravel + Inertia applications.

---

## 🧠 Concept

The package introduces a table key–based system:

- Each table has a unique identifier.
- Pagination and sorting state are scoped to that identifier.
- Multiple tables can exist on the same Inertia page without conflicts.
- The backend remains the single source of truth for data ordering and limits.

This approach keeps controllers clean while maintaining predictable frontend behavior.

---

## 🎯 Goals

- Provide a Laravel-native API similar to `->paginate()`
- Avoid manual query string management
- Support multiple tables on one page
- Keep pagination logic centralized
- Maintain full compatibility with Inertia.js

---

## 🐛 Issues & Support

Bug reports and feature requests are welcome.

Please open an issue in this repository:

👉 https://github.com/starter-solutions/inertia-data-table/issues

---

## 📄 License

MIT © Starter Solutions