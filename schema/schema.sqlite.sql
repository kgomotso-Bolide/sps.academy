-- SQLite mirror of schema.mysql.sql — for local development only.
--
-- This exists so the application can be built, run and tested without a MySQL
-- server. It is NOT the canonical schema; schema.mysql.sql is, and it carries
-- the commentary explaining why each column is there.
--
-- Same tables, same column names, same order. tools/migrate.php compares the
-- two files and refuses to run if they have drifted, because a development
-- database that quietly disagrees with production is worse than no development
-- database at all.

CREATE TABLE IF NOT EXISTS tenants (
  id            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  slug          TEXT    NOT NULL,
  name          TEXT    NOT NULL,
  academy_name  TEXT    NOT NULL,
  contact_email TEXT    NOT NULL,
  created_at    TEXT    NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_tenants_slug ON tenants (slug);


CREATE TABLE IF NOT EXISTS users (
  id            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id     INTEGER NOT NULL REFERENCES tenants (id),
  email         TEXT    NOT NULL,
  password_hash TEXT    NOT NULL,
  first_name    TEXT    NOT NULL,
  last_name     TEXT    NOT NULL,
  employee_no   TEXT        NULL,
  department    TEXT        NULL,
  role          TEXT    NOT NULL DEFAULT 'learner',
  status        TEXT    NOT NULL DEFAULT 'active',
  created_at    TEXT    NOT NULL,
  last_login_at TEXT        NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_tenant_email ON users (tenant_id, email);


CREATE TABLE IF NOT EXISTS registrations (
  id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id    INTEGER NOT NULL REFERENCES tenants (id),
  user_id      INTEGER     NULL REFERENCES users (id),
  course_slug  TEXT        NULL,
  course_title TEXT        NULL,
  full_name    TEXT    NOT NULL,
  email        TEXT    NOT NULL,
  phone        TEXT        NULL,
  employee_no  TEXT        NULL,
  department   TEXT        NULL,
  line_manager TEXT        NULL,
  message      TEXT        NULL,
  status       TEXT    NOT NULL DEFAULT 'new',
  source       TEXT    NOT NULL DEFAULT 'web',
  ip_hash      TEXT        NULL,
  user_agent   TEXT        NULL,
  created_at   TEXT    NOT NULL,
  purge_after  TEXT        NULL
);
CREATE INDEX IF NOT EXISTS ix_reg_tenant_created ON registrations (tenant_id, created_at);
CREATE INDEX IF NOT EXISTS ix_reg_purge ON registrations (purge_after);


CREATE TABLE IF NOT EXISTS consents (
  id              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id       INTEGER NOT NULL REFERENCES tenants (id),
  registration_id INTEGER     NULL REFERENCES registrations (id),
  user_id         INTEGER     NULL REFERENCES users (id),
  purpose         TEXT    NOT NULL,
  policy_version  TEXT    NOT NULL,
  granted         INTEGER NOT NULL DEFAULT 1,
  granted_at      TEXT    NOT NULL,
  ip_hash         TEXT        NULL
);
CREATE INDEX IF NOT EXISTS ix_consent_tenant ON consents (tenant_id);


CREATE TABLE IF NOT EXISTS audit_log (
  id             INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id      INTEGER     NULL,
  actor_user_id  INTEGER     NULL,
  action         TEXT    NOT NULL,
  entity         TEXT        NULL,
  entity_id      INTEGER     NULL,
  detail         TEXT        NULL,
  ip_hash        TEXT        NULL,
  created_at     TEXT    NOT NULL
);
CREATE INDEX IF NOT EXISTS ix_audit_tenant_created ON audit_log (tenant_id, created_at);
CREATE INDEX IF NOT EXISTS ix_audit_entity ON audit_log (entity, entity_id);


CREATE TABLE IF NOT EXISTS password_resets (
  id         INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id  INTEGER NOT NULL REFERENCES tenants (id),
  user_id    INTEGER NOT NULL REFERENCES users (id),
  token_hash TEXT    NOT NULL,
  expires_at TEXT    NOT NULL,
  used_at    TEXT        NULL,
  ip_hash    TEXT        NULL,
  created_at TEXT    NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_reset_token ON password_resets (token_hash);
CREATE INDEX IF NOT EXISTS ix_reset_user ON password_resets (tenant_id, user_id);
CREATE INDEX IF NOT EXISTS ix_reset_expires ON password_resets (expires_at);


CREATE TABLE IF NOT EXISTS enrolments (
  id              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id       INTEGER NOT NULL REFERENCES tenants (id),
  user_id         INTEGER NOT NULL REFERENCES users (id),
  course_slug     TEXT    NOT NULL,
  course_title    TEXT        NULL,
  registration_id INTEGER     NULL REFERENCES registrations (id),
  status          TEXT    NOT NULL DEFAULT 'active',
  enrolled_at     TEXT    NOT NULL,
  enrolled_by     INTEGER     NULL REFERENCES users (id),
  completed_at    TEXT        NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_enrol_user_course ON enrolments (tenant_id, user_id, course_slug);
CREATE INDEX IF NOT EXISTS ix_enrol_tenant_status ON enrolments (tenant_id, status);


CREATE TABLE IF NOT EXISTS learner_progress (
  id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id    INTEGER NOT NULL REFERENCES tenants (id),
  user_id      INTEGER NOT NULL REFERENCES users (id),
  course_slug  TEXT    NOT NULL,
  module_code  TEXT    NOT NULL,
  item_code    TEXT    NOT NULL DEFAULT '',
  completed_at TEXT    NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_lprog_item ON learner_progress (tenant_id, user_id, course_slug, module_code, item_code);
CREATE INDEX IF NOT EXISTS ix_lprog_user ON learner_progress (tenant_id, user_id);


CREATE TABLE IF NOT EXISTS progress_reports (
  id            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  tenant_id     INTEGER NOT NULL REFERENCES tenants (id),
  user_id       INTEGER     NULL REFERENCES users (id),
  full_name     TEXT    NOT NULL,
  email         TEXT    NOT NULL,
  employee_no   TEXT        NULL,
  line_manager  TEXT        NULL,
  qualification TEXT        NULL,
  summary       TEXT        NULL,
  detail        TEXT        NULL,
  message       TEXT        NULL,
  status        TEXT    NOT NULL DEFAULT 'new',
  ip_hash       TEXT        NULL,
  user_agent    TEXT        NULL,
  created_at    TEXT    NOT NULL,
  purge_after   TEXT        NULL
);
CREATE INDEX IF NOT EXISTS ix_prog_tenant_created ON progress_reports (tenant_id, created_at);
