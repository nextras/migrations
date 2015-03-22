ALTER TABLE "users"
ADD "language" char(2) NOT NULL DEFAULT 'en',
ADD FOREIGN KEY ("language") REFERENCES "languages" ("code");
