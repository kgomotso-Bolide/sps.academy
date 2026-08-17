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
