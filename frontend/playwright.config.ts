import { defineConfig, devices } from "@playwright/test";
export default defineConfig({
  testDir: "./e2e",
  reporter: "list",
  use: { baseURL: "http://127.0.0.1:8081", trace: "retain-on-failure" },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"] } },
    {
      name: "mobile",
      use: { ...devices["iPhone 13"], browserName: "chromium" },
    },
  ],
});
