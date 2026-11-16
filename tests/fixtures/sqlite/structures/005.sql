ALTER TABLE "users"
ADD "language" char(2) NOT NULL DEFAULT 'en' REFERENCES "languages" ("code");
