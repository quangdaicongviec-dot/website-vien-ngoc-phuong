# VietGemstones

## Mission
Create implementation-ready, token-driven UI guidance for VietGemstones that is optimized for consistency, accessibility, and fast delivery across e-commerce storefront.

## Brand
- Product/brand: VietGemstones
- URL: https://www.vietgemstones.com/
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations
- Visual style: structured, tokenized, content-first
- Main font style: `font.family.primary=Montserrat`, `font.family.stack=Montserrat, -apple-system, system-ui, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji`, `font.size.base=14.5px`, `font.weight.base=400`, `font.lineHeight.base=24.65px`
- Typography scale: `font.size.xs=13px`, `font.size.sm=14px`, `font.size.md=14.5px`, `font.size.lg=15px`, `font.size.xl=18px`, `font.size.2xl=24px`, `font.size.3xl=28px`, `font.size.4xl=36px`
- Color palette: `color.text.primary=#353333`, `color.text.secondary=#f7f8f7`, `color.text.tertiary=#666666`, `color.text.inverse=#587d6f`, `color.surface.base=#000000`, `color.surface.muted=#f9f9f9`, `color.surface.raised=#ffffff`, `color.surface.strong=#eef1ee`, `color.border.muted=#cccccc`
- Spacing scale: `space.1=1px`, `space.2=2px`, `space.3=5px`, `space.4=7px`, `space.5=10px`, `space.6=11.25px`, `space.7=13px`, `space.8=15px`
- Radius/shadow/motion tokens: `radius.xs=3px`, `radius.sm=5px`, `radius.md=6px`, `radius.lg=40px`, `radius.xl=50px`, `radius.2xl=999px` | `shadow.1=rgba(0, 0, 0, 0.2) 0px 8px 30px 0px` | `motion.duration.instant=200ms`, `motion.duration.fast=300ms`, `motion.duration.normal=500ms`

## Accessibility
- Target: WCAG 2.2 AA
- Keyboard-first interactions required.
- Focus-visible rules required.
- Contrast constraints required.

## Writing Tone
Concise, confident, implementation-focused.

## Rules: Do
- Use semantic tokens, not raw hex values, in component guidance.
- Every component must define states for default, hover, focus-visible, active, disabled, loading, and error.
- Component behavior should specify responsive and edge-case handling.
- Interactive components must document keyboard, pointer, and touch behavior.
- Accessibility acceptance criteria must be testable in implementation.

## Rules: Don't
- Do not allow low-contrast text or hidden focus indicators.
- Do not introduce one-off spacing or typography exceptions.
- Do not use ambiguous labels or non-descriptive actions.
- Do not ship component guidance without explicit state rules.

## Guideline Authoring Workflow
1. Restate design intent in one sentence.
2. Define foundations and semantic tokens.
3. Define component anatomy, variants, interactions, and state behavior.
4. Add accessibility acceptance criteria with pass/fail checks.
5. Add anti-patterns, migration notes, and edge-case handling.
6. End with a QA checklist.

## Required Output Structure
- Context and goals.
- Design tokens and foundations.
- Component-level rules (anatomy, variants, states, responsive behavior).
- Accessibility requirements and testable acceptance criteria.
- Content and tone standards with examples.
- Anti-patterns and prohibited implementations.
- QA checklist.

## Component Rule Expectations
- Include keyboard, pointer, and touch behavior.
- Include spacing and typography token requirements.
- Include long-content, overflow, and empty-state handling.
- Include known page component density: links (362), buttons (148), inputs (50), lists (19), navigation (2).


## Quality Gates
- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
