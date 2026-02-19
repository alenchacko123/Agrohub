# Google Pay Mockup - Visual Design Guide

## Payment Modal - Complete Flow

### Step 1: Initial Payment Method Selection
```
┌─────────────────────────────────────────────────────────┐
│  ✕                  Payment                             │
│                Secure payment gateway                    │
├─────────────────────────────────────────────────────────┤
│  ℹ️ Payment Details                                     │
│  Total Payable Amount                                    │
│         ₹16,500                                          │
│                                                          │
│      Choose Payment Method                               │
│                                                          │
│  ┌──────────────────┐  ┌──────────────────┐            │
│  │   💳             │  │   💰             │            │
│  │ Card Payment     │  │ Google Pay       │            │
│  │ Debit/Credit Card│  │ UPI Payment      │            │
│  └──────────────────┘  └──────────────────┘            │
└─────────────────────────────────────────────────────────┘
```

### Step 2: Google Pay Interface (After clicking Google Pay)
```
┌─────────────────────────────────────────────────────────┐
│  ✕                  Payment                             │
│                Secure payment gateway                    │
├─────────────────────────────────────────────────────────┤
│  ← Back to payment methods                               │
│                                                          │
│         ┌─────────────────────────┐                     │
│         │  💰 Google Pay          │                     │
│         └─────────────────────────┘                     │
│                                                          │
│   ┌─────────────────────────────────────────┐          │
│   │        Paying to                         │          │
│   │   AgroHub Equipment Rental              │          │
│   │                                          │          │
│   │         ₹16,500                          │          │
│   │                                          │          │
│   │     Equipment rental payment             │          │
│   └─────────────────────────────────────────┘          │
│                                                          │
│   Pay from                                               │
│   ┌─────────────────────────────────────────┐          │
│   │  [SB]  State Bank ••••1234       ✓      │          │
│   │        farmer@paytm                      │          │
│   └─────────────────────────────────────────┘          │
│                                                          │
│   ┌──────────┐  ┌────────────────────────┐            │
│   │  Cancel  │  │  🔒 Pay Securely       │            │
│   └──────────┘  └────────────────────────┘            │
│                                                          │
│         🛡️ Secured by Google Pay                        │
└─────────────────────────────────────────────────────────┘
```

### Step 3: Processing State
```
┌─────────────────────────────────────────────────────────┐
│  ✕                  Payment                             │
│                Secure payment gateway                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│                                                          │
│                   ⟳ Processing...                        │
│                                                          │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Step 4: Success State
```
┌─────────────────────────────────────────────────────────┐
│  ✕                  Payment                             │
│                Secure payment gateway                    │
├─────────────────────────────────────────────────────────┤
│                                                          │
│              ┌───────┐                                   │
│              │   ✓   │  (Green circle with check)      │
│              └───────┘                                   │
│                                                          │
│           Payment Successful!                            │
│     Your payment via Google Pay has been completed       │
│                                                          │
│   ┌─────────────────────────────────────────┐          │
│   │  Amount Paid:             ₹16,500       │          │
│   │  Transaction ID:   TXN1707567890123     │          │
│   └─────────────────────────────────────────┘          │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Color Scheme

### Google Pay Colors
- **Primary Blue**: `#1a73e8` (Buttons, amount)
- **Secondary Blue**: `#4285f4` (Gradients)
- **Success Green**: `#10b981`, `#059669`
- **Background**: `#ffffff`, `#f8f9fa`

### UI Elements
- **White Cards**: Clean white with subtle borders `#e0e0e0`
- **Text Colors**:
  - Primary: `#333`
  - Secondary: `#666`
  - Light: `#999`

### Shadows and Depth
```css
box-shadow: 0 4px 20px rgba(0,0,0,0.08);  /* Card containers */
box-shadow: 0 2px 8px rgba(0,0,0,0.1);    /* Header badge */
box-shadow: 0 4px 12px rgba(26,115,232,0.3); /* Pay button */
```

## Interactive States

### Hover Effects
1. **Payment Method Cards**: Border changes to blue `#1a73e8`
2. **Pay Button**: Lifts up 2px, shadow increases
3. **Cancel Button**: Background changes to `#f5f5f5`

### Transitions
- All transitions: `0.2s - 0.3s` ease
- Smooth, responsive feel

## Typography

### Sizes
- **Main Amount**: `2.5rem`, bold, blue
- **Headers**: `1.1rem - 1.5rem`, semi-bold
- **Body Text**: `0.85rem - 1rem`
- **Small Text**: `0.75rem - 0.85rem`

### Weights
- **Bold**: 700 (amounts, headings)
- **Semi-bold**: 600 (labels)
- **Regular**: 400 (body text)

## Spacing System
- **XS**: `0.5rem` (8px)
- **SM**: `0.75rem` (12px)
- **MD**: `1rem` (16px)
- **LG**: `1.5rem` (24px)
- **XL**: `2rem` (32px)

## Border Radius
- **Small**: `8px` (inputs)
- **Medium**: `12px` (cards, buttons)
- **Large**: `16px` (main containers)
- **Pill**: `50px` (badges, cancel button)
- **Circle**: `50%` (bank icon)

## Accessibility Features
✅ High contrast text (WCAG AA compliant)
✅ Clear visual hierarchy
✅ Large touch targets (min 44px)
✅ Keyboard navigation support
✅ Screen reader friendly labels

## Responsive Behavior
- Full width on mobile
- Max-width container on desktop
- Stack buttons vertically on small screens
- Maintain readability at all sizes

## Animation Timing
- **Page Load**: Fade in, 0.3s
- **Modal Open**: Slide up, 0.4s cubic-bezier
- **Button Hover**: 0.2s ease
- **Processing Spin**: 1s linear infinite
- **Success Scale**: 0.5s ease-out

## Implementation Notes

### Key Features
1. **Authentic Design**: Matches real Google Pay UI patterns
2. **Smooth Animations**: Professional feel with CSS transitions
3. **Realistic Flow**: Multi-step process mimics actual payment
4. **Error Handling**: Graceful fallbacks for failed payments
5. **Security Indicators**: Lock icon, "Secured by" badge

### Integration Points
- Uses existing `window.currentPaymentAmount` for amount
- Calls `processPayment()` after success animation
- Integrates with backend payment processing
- Updates agreement status automatically

## Testing Checklist
- [ ] Click Google Pay option
- [ ] Verify amount displays correctly
- [ ] Test hover effects on all buttons
- [ ] Confirm processing animation works
- [ ] Check success state appears
- [ ] Verify backend payment completes
- [ ] Test "Back" button navigation
- [ ] Ensure mobile responsiveness
