"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { LogIn } from "lucide-react";
import { apiClient } from "@/lib/apiClient";

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      // Step 1: Initialize CSRF protection (Sanctum/Laravel standard)
      // Since this might not be Sanctum but Passport or standard web guard,
      // we'll try a common approach. If it's pure passport, we'd use /oauth/token.
      // Let's assume standard Laravel auth via /login for stateful session 
      // or a custom endpoint in JerryUpdates.
      
      // Let's first ensure we have CSRF token (for cookie based auth)
      try {
        await apiClient.get("/sanctum/csrf-cookie", { baseURL: process.env.NEXT_PUBLIC_API_URL?.replace('/api/jerryupdates/v1', '') || 'http://fwcv3.test' });
      } catch (e) {
        // Might fail if not using sanctum, ignore
        console.log("CSRF cookie request failed, continuing anyway...", e);
      }

      // Try hitting the standard Laravel login endpoint first
      const baseURL = process.env.NEXT_PUBLIC_API_URL?.replace('/api/jerryupdates/v1', '') || 'http://fwcv3.test';
      
      const response = await apiClient.post(`${baseURL}/login`, {
        username: email, // UltimatePOS uses username by default, but fallback to email
        password: password,
      }, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.status === 200 || response.status === 204) {
        // Success
        router.push("/pos");
        return;
      }
    } catch (err: any) {
      console.error("Login error:", err);
      
      // Try OAuth token if standard login fails (Passport fallback)
      try {
        const baseURL = process.env.NEXT_PUBLIC_API_URL?.replace('/api/jerryupdates/v1', '') || 'http://fwcv3.test';
        // In Ultimate POS, they often use a specific OAuth endpoint or custom auth
        const oauthResponse = await apiClient.post(`${baseURL}/oauth/token`, {
          grant_type: 'password',
          client_id: 2, // Default password client ID
          client_secret: 'YOUR_CLIENT_SECRET_HERE', // This would need to be in env
          username: email,
          password: password,
        });
        
        if (oauthResponse.data && oauthResponse.data.access_token) {
          // Store token and set header
          localStorage.setItem('auth_token', oauthResponse.data.access_token);
          apiClient.defaults.headers.common['Authorization'] = `Bearer ${oauthResponse.data.access_token}`;
          router.push("/pos");
          return;
        }
      } catch (oauthErr) {
        console.error("OAuth fallback failed:", oauthErr);
      }
      
      setError(
        err.response?.data?.message || err.response?.data?.msg || "Failed to log in. Check your credentials."
      );
    } finally {
      setLoading(false);
    }
  };

  // For the JerryUpdates POC, let's also add a "Dev Bypass" button
  const handleDevBypass = () => {
    localStorage.setItem('dev_bypass', 'true');
    router.push("/pos");
  };

  return (
    <div className="min-h-screen bg-[#f5f5f7] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center w-16 h-16 mx-auto bg-black rounded-2xl items-center shadow-lg">
          <span className="text-white font-bold text-2xl">POS</span>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900 tracking-tight">
          Tradex Headless POS
        </h2>
        <p className="mt-2 text-center text-sm text-gray-600">
          Sign in to your account
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-black/5">
          <form className="space-y-6" onSubmit={handleLogin}>
            <div>
              <label
                htmlFor="email"
                className="block text-sm font-medium text-gray-700"
              >
                Username or Email
              </label>
              <div className="mt-1">
                <input
                  id="email"
                  name="email"
                  type="text"
                  autoComplete="username"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-black focus:border-black sm:text-sm"
                  placeholder="admin"
                />
              </div>
            </div>

            <div>
              <label
                htmlFor="password"
                className="block text-sm font-medium text-gray-700"
              >
                Password
              </label>
              <div className="mt-1">
                <input
                  id="password"
                  name="password"
                  type="password"
                  autoComplete="current-password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-black focus:border-black sm:text-sm"
                  placeholder="••••••••"
                />
              </div>
            </div>

            {error && (
              <div className="rounded-xl bg-red-50 p-4">
                <div className="flex">
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">{error}</h3>
                  </div>
                </div>
              </div>
            )}

            <div>
              <button
                type="submit"
                disabled={loading}
                className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black disabled:opacity-50 transition-colors"
              >
                {loading ? (
                  "Signing in..."
                ) : (
                  <>
                    <LogIn className="w-5 h-5 mr-2" />
                    Sign in
                  </>
                )}
              </button>
            </div>
            
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">For Development</span>
              </div>
            </div>

            <div>
              <button
                type="button"
                onClick={handleDevBypass}
                className="w-full flex justify-center py-2.5 px-4 border border-gray-300 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors"
              >
                Bypass Login (Dev Mode)
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
