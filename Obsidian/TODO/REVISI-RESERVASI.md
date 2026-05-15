# Revision Tasks: Reservations Module
Date: 2026-05-13

## 1. UI/UX Cleanup & Searchable Partner
- [x] **Remove Fields**: Remove "Asal Tamu" (Origin) and "Detail Nama Hotel / Agent" from the `/reservations/create` form, as this information is already linked via the Partner selection.
- [x] **Searchable Dropdown**: Implement a searchable dropdown (e.g., Select2 or Datalist) for the "Partner" field to facilitate quick searching and selection.

## 2. Dynamic Product Filtering
- [x] **Category-based Filtering**: Ensure the "Product" dropdown only displays items relevant to the selected Partner.
- [x] **Logic**: Fetch products that match the Partner's category when a Partner is selected.

## 3. Product Details Enhancement
- [x] **Metadata Display**: Add "Foreign / Domestic" status to the product selection/details.
- [x] **Payment Method**: Include the "Payment Method" information associated with the product.
- [x] **Database/Model Update**: (If necessary) Ensure these fields are available in the Product model and visible in the UI.
