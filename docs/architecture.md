# Aura v2 — Architecture Context

## Purpose
Aura is a multi-tenant clinical management system focused on:
- Patient activity tracking
- Treatments, appointments and payments
- Financial traceability per patient and clinic

Aura is NOT:
- A full medical record system
- An agenda/scheduling system
- A CRM

## Core Principles
- Multi-tenant by `clinic_id` (mandatory in all tables)
- Hub & Spoke model:
  - Patient is a pure identity core
  - Treatments, appointments, payments, invoices are satellites
- No calculated fields persisted in database
- All balances are calculated dynamically
- Soft deletes enabled on all operational tables

## Identifiers
- patient_code: PC-{seq}-{YY}, unique per clinic
- invoice_number: INV-{seq}-{YY}, unique per clinic

## Architectural Rules
- Never store aggregates (total_spent, balance, last_visit)
- Never use DNI/email as foreign keys
- Always filter by clinic_id
- Global scopes must enforce tenant isolation

## v1 Scope
Tables included in v1:
- clinics
- patients
- treatments
- appointments
- invoices
- payments
