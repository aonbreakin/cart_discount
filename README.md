# Discount Module 

**Author:** Jirapat Chutithadakul
**Language:** PHP 8+

## Overview
This module calculates the final price of a shopping cart by applying multiple discount campaigns:
- Coupon (fixed or percentage)
- On Top (category or points)
- Seasonal (fixed amount per threshold)

Discount order:
1. Coupon
2. On Top
3. Seasonal

## How to Run
1. Clone repository
2. Start PHP server:
   ```bash
   php -S localhost:8000

some change
UI can input discount to discount list want to add more
 -Point > Maximum point %(percentage of Total price is maximum point can discount) 
 -Value for discount add more like 
  -Fixed > input discount price -Percentage > input discount percentage
 -Percentage disount by category > input category, percent
 -Point > input point, exchange rate point per baht
UI can input item to item list want to fill add more
 - item qty.(quantity of item is quantity to pick up)
Output want to add more
 - Maximum point discount: (Show maximum point unable to discount (show when select Discount by point in point discount))
Theme > Light modern UI (white/gray theme)