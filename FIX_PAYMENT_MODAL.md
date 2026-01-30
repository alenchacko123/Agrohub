# Issue Resolved: Payment Modal Not Opening

## The Issues
1. **Broken Modal Structure**: The Payment Modal code was accidentally nested *inside* the Insurance Modal's form. This happened during a previous edit where the closing tags for the Insurance Modal were overwritten.
2. **Missing Input**: The "Total Premium" input field for insurance was also accidentally removed.

## The Fixes
1. **Separated Modals**: Relocated the Payment Modal code to be outside of the Insurance Modal. They are now two distinct, independent modals.
2. **Restored Insurance Form**: Added back the "Total Premium" input field and label to the Insurance Modal.
3. **Verified IDs**: Ensured the Payment Modal has the correct ID (`qrPaymentModal`) so the JavaScript can find and open it.

## Result
- **Clicking "Proceed to Payment"** will now properly open the Payment Modal.
- The modal will show the **"Choose Payment Method"** screen (Card vs Google Pay).
- The Insurance Purchase modal will also work correctly again.

## Files Modified
- `agreements.html`

## How to Test
1. Refresh `agreements.html`.
2. Click **"Proceed to Payment"** on a farmer's agreement.
3. ✅ The Payment Selection modal should appear.
4. (Optional) Check the "Purchase Insurance" modal to ensure it also looks correct.
