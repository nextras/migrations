CREATE TABLE "users" (
  "id" serial4 NOT NULL,
  "name" varchar(100) NOT NULL,
  "password_hash" char(60) NOT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "name" UNIQUE ("name")
);
