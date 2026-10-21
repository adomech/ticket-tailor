import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { reportSchema } from "./schema";

it("parses the committed dashboard contract", () => {
  const fixture = JSON.parse(
    readFileSync(
      resolve(process.cwd(), "../docs/fixtures/dashboard.json"),
      "utf8",
    ),
  );
  expect(reportSchema.parse(fixture).earnings.net).toBe(98864);
});
