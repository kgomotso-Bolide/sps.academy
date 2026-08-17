-- Centenary Networks academy platform — MySQL schema (canonical).
--
-- ONE database, one row per company in `tenants`, and a tenant_id on every
-- table that holds personal information. The alternative — a database per
-- company — isolates harder but multiplies by four every migration, backup and
-- credential. With one database the isolation has to be enforced in code, so it
-- is enforced in exactly one place: tenant_id() in lib/db.php reads the tenant
-- from a config file that lives outside the web root, and nothing derives it
-- from anything a visitor can send.
--
-- schema/schema.sqlite.sql mirrors this file table for table and column for
-- column so the application can run on a laptop. tools/migrate.php compares the
-- two and refuses to run if they have drifted apart.
--
-- POPIA notes are inline. They are not decoration: each one is the reason a
-- column exists, or the reason a column deliberately does not.

CREATE TABLE IF NOT EXISTS tenants (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(40)  NOT NULL,
  name          VARCHAR(120) NOT NULL,
  academy_name  VARCHAR(120) NOT NULL,
  contact_email VARCHAR(190) NOT NULL,
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tenants_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Learners and administrators.
--
-- No ID number column, and that is deliberate for now. A South African ID
-- number is needed to register a learner with the QCTO, but it is not needed to
-- express interest in a course, and POPIA's minimality condition says we do not
-- collect it until the purpose exists. It gets added with the enrolment tables,
-- with its own consent, not before.
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  employee_no   VARCHAR(40)      NULL,
  department    VARCHAR(120)     NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'learner',
  status        VARCHAR(20)  NOT NULL DEFAULT 'active',
  created_at    DATETIME     NOT NULL,
  last_login_at DATETIME         NULL,
  PRIMARY KEY (id),
  -- Scoped to the tenant, not global: the same person could legitimately be a
  -- learner at two of these companies, and one must not see the other.
  UNIQUE KEY uq_users_tenant_email (tenant_id, email),
  CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Course registrations — what the FormSubmit form used to email away.
--
-- The row is now the record. Under FormSubmit the only copy lived in one
-- person's inbox, on a third-party service outside South Africa, with no
-- retention rule and no way to answer "what do you hold about me".
CREATE TABLE IF NOT EXISTS registrations (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED     NULL,
  course_slug  VARCHAR(80)      NULL,
  course_title VARCHAR(190)     NULL,
  full_name    VARCHAR(160) NOT NULL,
  email        VARCHAR(190) NOT NULL,
  phone        VARCHAR(40)      NULL,
  employee_no  VARCHAR(40)      NULL,
  department   VARCHAR(120)     NULL,
  line_manager VARCHAR(160)     NULL,
  message      TEXT             NULL,
  status       VARCHAR(20)  NOT NULL DEFAULT 'new',
  source       VARCHAR(40)  NOT NULL DEFAULT 'web',
  -- Hashed, never the address itself. See 'ip_pepper' in lib/config.sample.php.
  ip_hash      CHAR(64)         NULL,
  user_agent   VARCHAR(255)     NULL,
  created_at   DATETIME     NOT NULL,
  -- POPIA s14: personal information is not kept longer than the purpose needs.
  -- A date on the row makes deletion a scheduled job rather than a good
  -- intention. Registrations that never become enrolments expire; the ones that
  -- do are carried over to the enrolment record with its own longer basis,
  -- because QCTO record-keeping obliges us to retain those.
  purge_after  DATE             NULL,
  PRIMARY KEY (id),
  KEY ix_reg_tenant_created (tenant_id, created_at),
  KEY ix_reg_purge (purge_after),
  CONSTRAINT fk_reg_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  CONSTRAINT fk_reg_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- What each person agreed to, and when.
--
-- Consent that cannot be evidenced is not consent. policy_version pins the row
-- to the wording that was on screen at the time, so "I never agreed to that"
-- has an answer years later, after the notice has been revised twice.
CREATE TABLE IF NOT EXISTS consents (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       INT UNSIGNED NOT NULL,
  registration_id INT UNSIGNED     NULL,
  user_id         INT UNSIGNED     NULL,
  purpose         VARCHAR(60)  NOT NULL,
  policy_version  VARCHAR(20)  NOT NULL,
  granted         TINYINT(1)   NOT NULL DEFAULT 1,
  granted_at      DATETIME     NOT NULL,
  ip_hash         CHAR(64)         NULL,
  PRIMARY KEY (id),
  KEY ix_consent_tenant (tenant_id),
  CONSTRAINT fk_consent_tenant FOREIGN KEY (tenant_id)       REFERENCES tenants (id),
  CONSTRAINT fk_consent_reg    FOREIGN KEY (registration_id) REFERENCES registrations (id),
  CONSTRAINT fk_consent_user   FOREIGN KEY (user_id)         REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Who did what to whose data.
--
-- POPIA s19 requires reasonable measures to secure personal information, and an
-- access trail is the one that makes the others checkable. It is also the only
-- way to answer the question the Information Regulator asks after a breach:
-- what was reached, and by whom.
CREATE TABLE IF NOT EXISTS audit_log (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id      INT UNSIGNED     NULL,
  actor_user_id  INT UNSIGNED     NULL,
  action         VARCHAR(60)  NOT NULL,
  entity         VARCHAR(60)      NULL,
  entity_id      INT UNSIGNED     NULL,
  detail         VARCHAR(500)     NULL,
  ip_hash        CHAR(64)         NULL,
  created_at     DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY ix_audit_tenant_created (tenant_id, created_at),
  KEY ix_audit_entity (entity, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Password reset links.
--
-- The token itself is NEVER stored. What is stored is its SHA-256 hash, for the
-- same reason passwords are hashed: this table is the one an attacker who gets a
-- read of the database would go to first, and a table of live reset tokens is a
-- table of working sign-ins for every account in it. A hash makes the stolen
-- copy worthless.
--
-- Rows are kept after use rather than deleted, and used_at is why: "somebody
-- reset this account's password on the 3rd" is a question that gets asked after
-- an incident, and a deleted row cannot answer it. They carry no personal
-- information beyond the user id.
--
-- No email column. The address is on the user row; copying it here would create
-- a second place to find every learner's email address, with its own lifetime.
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id  INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,
  expires_at DATETIME     NOT NULL,
  used_at    DATETIME         NULL,
  ip_hash    CHAR(64)         NULL,
  created_at DATETIME     NOT NULL,
  PRIMARY KEY (id),
  -- Unique on the hash, so two tokens can never collide into one account.
  UNIQUE KEY uq_reset_token (token_hash),
  KEY ix_reset_user (tenant_id, user_id),
  KEY ix_reset_expires (expires_at),
  CONSTRAINT fk_reset_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  CONSTRAINT fk_reset_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Who is on which course.
--
-- A registration is an expression of interest; an enrolment is a decision. They
-- are separate rows because they answer different questions and expire on
-- different clocks: a registration that goes nowhere is deleted after twelve
-- months, while an enrolment is the start of a learner record the QCTO obliges
-- us to keep.
--
-- The row is created by an administrator pressing "Enrol" on the registrations
-- list, which is also the moment the learner's account is created. There is no
-- self-service route to this table, deliberately: the site is on a public URL
-- and we have no working email verification, so "anyone who can type an address
-- can create an account" would be the whole access control.
--
-- enrolled_by names the administrator who did it. When somebody asks in a year
-- why a person has access to material, the answer needs to be a name and a
-- date, not an inference from created_at.
CREATE TABLE IF NOT EXISTS enrolments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id       INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  course_slug     VARCHAR(60)  NOT NULL,
  course_title    VARCHAR(190)     NULL,
  registration_id INT UNSIGNED     NULL,
  status          VARCHAR(20)  NOT NULL DEFAULT 'active',
  enrolled_at     DATETIME     NOT NULL,
  enrolled_by     INT UNSIGNED     NULL,
  completed_at    DATETIME         NULL,
  PRIMARY KEY (id),
  -- One enrolment per person per course. Pressing "Enrol" twice is a slip, not
  -- a second enrolment, and the database says so rather than the application
  -- remembering to.
  UNIQUE KEY uq_enrol_user_course (tenant_id, user_id, course_slug),
  KEY ix_enrol_tenant_status (tenant_id, status),
  CONSTRAINT fk_enrol_tenant FOREIGN KEY (tenant_id)       REFERENCES tenants (id),
  CONSTRAINT fk_enrol_user   FOREIGN KEY (user_id)         REFERENCES users (id),
  CONSTRAINT fk_enrol_reg    FOREIGN KEY (registration_id) REFERENCES registrations (id),
  CONSTRAINT fk_enrol_by     FOREIGN KEY (enrolled_by)     REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- A learner's ticked-off topics, one row per tick.
--
-- This replaces localStorage, which is where progress lived while there was no
-- backend. That was defensible on static hosting and is not defensible now: it
-- was tied to one browser on one device, it vanished when somebody cleared
-- their history, and on a shared site machine the next learner saw the previous
-- learner's record. None of those are bugs in the old code — they are what
-- browser storage is — and all three are why accounts exist.
--
-- A row means "done". Un-ticking DELETES the row rather than setting a flag,
-- because a tick a learner has taken back is not information we have a purpose
-- for holding. POPIA's minimality condition cuts both ways: it is also about
-- not keeping what has stopped being needed.
--
-- item_code is the topic ('KM-01-KT01'). The empty string is not a missing
-- value — it is the module-level row, meaning the learner marked the whole
-- module complete. It is '' rather than NULL because MySQL and SQLite both
-- allow duplicate NULLs through a UNIQUE index, so a nullable column here would
-- silently stop the uniqueness constraint from doing its job.
--
-- No purge_after, for the same reason progress_reports has none: this is part
-- of the learner record against an accredited qualification.
CREATE TABLE IF NOT EXISTS learner_progress (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id    INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  course_slug  VARCHAR(60)  NOT NULL,
  module_code  VARCHAR(20)  NOT NULL,
  item_code    VARCHAR(40)  NOT NULL DEFAULT '',
  completed_at DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lprog_item (tenant_id, user_id, course_slug, module_code, item_code),
  KEY ix_lprog_user (tenant_id, user_id),
  CONSTRAINT fk_lprog_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  CONSTRAINT fk_lprog_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Learner progress reports — what pm-progress.html used to email to FormSubmit.
--
-- Same third-party problem as registrations, and arguably worse: a progress
-- report says how far behind somebody is, which is exactly the sort of thing an
-- employee would not expect to be sitting on a service outside the country.
--
-- No purge_after value is set on these rows, deliberately and not by oversight.
-- A progress report against an accredited qualification is part of the learner
-- record the QCTO obliges us to retain, so it does not expire on the twelve-month
-- rule that registrations use. The exact period the QCTO requires is still
-- outstanding — it is one of the three items marked "to confirm" on the privacy
-- notice, and this column is where the answer gets applied once we have it.
CREATE TABLE IF NOT EXISTS progress_reports (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED NOT NULL,
  user_id       INT UNSIGNED     NULL,
  full_name     VARCHAR(160) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  employee_no   VARCHAR(40)      NULL,
  line_manager  VARCHAR(160)     NULL,
  qualification VARCHAR(190)     NULL,
  summary       VARCHAR(500)     NULL,
  detail        TEXT             NULL,
  message       TEXT             NULL,
  status        VARCHAR(20)  NOT NULL DEFAULT 'new',
  ip_hash       CHAR(64)         NULL,
  user_agent    VARCHAR(255)     NULL,
  created_at    DATETIME     NOT NULL,
  purge_after   DATE             NULL,
  PRIMARY KEY (id),
  KEY ix_prog_tenant_created (tenant_id, created_at),
  CONSTRAINT fk_prog_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
  CONSTRAINT fk_prog_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
