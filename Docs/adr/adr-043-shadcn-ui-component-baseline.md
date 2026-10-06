# ADR-043 — shadcn/ui sebagai Komponen UI Frontend

**Status:** Accepted
**Date:** 2026-10-05
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context

Frontend React + TypeScript + Vite (ADR-002) membutuhkan baseline komponen UI untuk backoffice desktop-first (table, filter, form, dialog) dan workspace EOS mobile-first. `ux.md` dan `ui-spec.md` tidak menetapkan component library; keputusan visual/struktur komponen dibutuhkan sebelum implementasi frontend dimulai.

## Decision

Seluruh UI menggunakan shadcn/ui secara penuh sebagai baseline, termasuk shadcn blocks. Pemilihan block spesifik untuk tiap workspace/layar adalah keputusan implementasi frontend saat pengerjaan berlangsung; ADR ini tidak mengikat block tertentu.

Routing memakai React Router v7 framework mode di atas Vite (SPA murni, static build). Next.js dan TanStack Start tidak dipilih: aplikasi internal tanpa kebutuhan SEO/SSR, data selalu via Go REST API dengan cookie session, sehingga runtime Node server tambahan hanya menambah beban operasional tanpa manfaat.

## Consequences

- shadcn/ui bukan dependency runtime terpisah: komponen (Radix primitives + Tailwind) disalin ke repository dan dimiliki kode sendiri, dapat dimodifikasi sesuai kebutuhan status/domain (badge attendance, lifecycle attachment, penanda tanggal merah kalender).
- Komponen di luar cakupan shadcn tetap dibangun custom di atas baseline: capture selfie (`getUserMedia` + preview + fallback `input capture`), geolocation flow, peta Leaflet/react-leaflet (ADR-019), dan checklist renderer dinamis dari master data `checklist_*`.
- UI tetap bukan authority: keputusan bisnis/waktu/geofence tetap di backend (tech-stack.md §2).
- Tailwind dan Radix masuk sebagai dependency frontend; konvensi theme token (warna status success/warning/danger) ditetapkan konsisten sebelum implementasi screen massal.
- Blocks shadcn ditulis untuk Next.js; block yang dipilih di-porting sekali per block saat implementasi: `next/link` → Link react-router, `next/navigation` → hook react-router, `"use client"` dihapus, server actions diganti panggilan Go API.
- Responsive EOS (2026-10-05): di viewport desktop, workspace EOS tetap kolom tunggal 720px secara default; pengecualian layout lebar hanya Daily Report (sidebar section) dan halaman historis read-only EOS (ux.md §6.3/§10, ui-spec.md §5). Flow kamera/GPS tetap pola mobile di semua ukuran layar. Komponen responsif untuk pengecualian ini dibangun di atas baseline shadcn yang sama.
- Deploy frontend tetap static asset di web container Dokploy (ADR-007/ADR-030) — tidak ada Node runtime server tambahan; PWA (service worker, manifest, offline draft ST-4.11) via `vite-plugin-pwa`.

## Alternatives considered

- Component library siap pakai lain (Mantine, HeroUI, Ant Design): tidak dipilih; shadcn dipilih atas kecepatan dan kepemilikan kode komponen.
- Custom component system dari nol: lebih lambat, tidak memberi nilai tambah untuk kebutuhan backoffice.
- Next.js: tidak dipilih — internal app tanpa SEO/SSR; menambah Node runtime server yang harus dioperasikan di samping Go API + worker.
- TanStack Start: tidak dipilih — nilai utamanya (type-safe routing, SSR loaders) sudah tersedia di React Router v7 framework mode dengan ekosistem lebih matang.

## References

tech-stack.md §3–4; ux.md §2; ui-spec.md §7; ADR-002; ADR-019
