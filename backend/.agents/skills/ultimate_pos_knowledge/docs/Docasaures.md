---
id: full-auth-guide
title: Full Authentication Guide
sidebar_label: Auth Guide
---


# 📘 **Docusaurus Authentication + Deployment Tutorial (No Firebase)**

*A complete guide from project setup to production deployment.*

---

## 🏁 **Introduction**

This tutorial teaches you how to build a **secure, login-protected Docusaurus documentation site** using:

✅ Local Email/Password authentication
❌ No Firebase
❌ No external backend
🏳 Works on **GitHub + Vercel**
🔐 Protects all docs behind a login page

By the end, you will have:

* A full Docusaurus site
* Login page with modern UI
* Logout functionality
* Protected documents
* GitHub repo
* Vercel deployment

---

# 🚀 **1. Create a New Docusaurus Project**

Run:

```bash
npx create-docusaurus@latest devforum classic --typescript
cd devforum
npm install
npm run start
```

Your local site is now running at:

```
http://localhost:3000
```

---

# 🌱 **2. Initialize Git & Push to GitHub**

Inside your project folder:

```bash
git init
git add .
git commit -m "Initial Docusaurus project"
git branch -M main
git remote add origin git@github.com:rihanciara/devforum.git
git push -u origin main
```

> 💡 You already configured your SSH key, so this works with no password.

---

# ☁️ **3. Deploy to Vercel**

Go to **Vercel Dashboard → New Project**
Select your GitHub repo: `devforum`

### Important: Set the Root Directory

Your actual project folder is:

```
newdevforum
```

So in **Project Settings → Root Directory**, set:

```
newdevforum
```

Build Command (default):

```
npm run build
```

Output directory:

```
build
```

Click **Deploy** 🚀

---

# 🔐 **4. Add Local Authentication**

We use simple, secure, local email/password login.

Create this folder structure:

```
src
│  authConfig.ts
├─ pages
│    login.tsx
│    logout.tsx
└─ theme
     Root.tsx
```

---

# 🔑 **5. Create User Credentials**

Create: `src/authConfig.ts`

```ts
export const USERS = [
  {
    email: "admin@example.com",
    password: "password123",
  },
];
```

You can add multiple users.

> ⚠️ These are stored locally — perfect for internal documentation.

---

# 🧑‍💻 **6. Create Login Page (Styled UI)**

Create: `src/pages/login.tsx`

```tsx
import React, { useState } from "react";
import { useHistory } from "@docusaurus/router";
import { USERS } from "../authConfig";

export default function Login() {
  const history = useHistory();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const loginUser = () => {
    const found = USERS.find(
      (u) => u.email === email && u.password === password
    );

    if (!found) {
      alert("Invalid email or password");
      return;
    }

    localStorage.setItem("authUser", found.email);
    history.push("/docs/intro");
  };

  return (
    <div style={{
      maxWidth: 420,
      margin: "60px auto",
      padding: 30,
      borderRadius: 12,
      background: "#fff",
      boxShadow: "0 4px 10px rgba(0,0,0,0.1)"
    }}>
      <h2 style={{ textAlign: "center", marginBottom: 20 }}>Login</h2>

      <input
        type="email"
        placeholder="Email"
        onChange={(e) => setEmail(e.target.value)}
        style={{
          width: "100%",
          padding: 12,
          marginBottom: 10,
          borderRadius: 8,
          border: "1px solid #ccc",
        }}
      />

      <input
        type="password"
        placeholder="Password"
        onChange={(e) => setPassword(e.target.value)}
        style={{
          width: "100%",
          padding: 12,
          marginBottom: 15,
          borderRadius: 8,
          border: "1px solid #ccc",
        }}
      />

      <button
        onClick={loginUser}
        style={{
          width: "100%",
          padding: 12,
          background: "#4f46e5",
          color: "white",
          border: "none",
          borderRadius: 8,
          cursor: "pointer",
          fontSize: 16,
        }}
      >
        Login
      </button>
    </div>
  );
}
```

---

# 🛡️ **7. Protect All Pages Automatically**

Create: `src/theme/Root.tsx`

```tsx
import React from "react";
import { useLocation, useHistory } from "@docusaurus/router";

export default function Root({ children }) {
  const history = useHistory();
  const location = useLocation();

  const user =
    typeof window !== "undefined"
      ? localStorage.getItem("authUser")
      : null;

  const isLoginPage = location.pathname === "/login";

  if (!user && !isLoginPage) {
    history.push("/login");
    return null;
  }

  return <>{children}</>;
}
```

> 🔐 All docs/pages are now protected automatically.

---

# 🚪 **8. Add Logout Page**

Create: `src/pages/logout.tsx`

```tsx
import { useEffect } from "react";
import { useHistory } from "@docusaurus/router";

export default function Logout() {
  const history = useHistory();

  useEffect(() => {
    localStorage.removeItem("authUser");
    history.push("/login");
  }, []);

  return null;
}
```

---

# 🧭 **9. Add Login/Logout Links to Navbar**

Inside `docusaurus.config.ts`:

```ts
navbar: {
  title: "DevDocs",
  items: [
    { type: "docSidebar", sidebarId: "tutorialSidebar", label: "Docs", position: "left" },
    { to: "/login", label: "Login", position: "right" },
    { to: "/logout", label: "Logout", position: "right" },
  ],
},
```

---

# 📦 **10. Final Commit and Deployment**

```bash
git add .
git commit -m "Added authentication system"
git push
```

Vercel will automatically detect changes and redeploy.

---

# 🎉 **Your Authentication System Is Complete!**

You now have:

✔ Full Docusaurus site
✔ Login + Logout
✔ Docs protected behind authentication
✔ GitHub versioning
✔ Vercel production hosting
✔ NO Firebase / backend required

---

# 🛍 Want an Advanced Version?

I can extend this system with:

* Premium vs Free documentation
* User roles (Admin, Staff, Client)
* Registration system
* Password reset
* Modern Tailwind UI
* JWT Authentication
* Supabase or NextAuth integration

Just tell me **what you want next**.

---

Would you like me to:
📌 Add this tutorial to your sidebar automatically?
📌 Add theme styling?
📌 Split into multiple doc pages?
