
"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Home, Camera, Newspaper, ShoppingBasket, User } from "lucide-react";
import { cn } from "@/lib/utils";

const navItems = [
  { href: "/", label: "Home", icon: Home },
  { href: "/analysis", label: "Analysis", icon: Camera },
  { href: "/news", label: "News", icon: Newspaper },
  { href: "/marketplace", label: "Market", icon: ShoppingBasket },
  { href: "/profile", label: "Profile", icon: User },
];

export function Navbar() {
  const pathname = usePathname();

  return (
    <>
      {/* Desktop Header */}
      <nav className="fixed top-0 left-0 right-0 h-16 bg-white border-b hidden md:flex items-center z-50 px-8">
        <div className="max-w-7xl mx-auto w-full flex justify-between items-center">
          <Link href="/" className="text-2xl font-bold text-primary flex items-center gap-2">
            <span className="p-1.5 bg-primary rounded-lg text-white">
              <ShoppingBasket size={24} />
            </span>
            Drfarm
          </Link>
          <div className="flex gap-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "flex items-center gap-2 px-4 py-2 rounded-md transition-colors font-medium",
                    isActive
                      ? "bg-primary/10 text-primary"
                      : "text-muted-foreground hover:bg-secondary"
                  )}
                >
                  <Icon size={20} />
                  {item.label}
                </Link>
              );
            })}
          </div>
        </div>
      </nav>

      {/* Mobile Bottom Navigation */}
      <nav className="fixed bottom-0 left-0 right-0 h-16 bg-white border-t flex md:hidden items-center justify-around z-50 pb-safe">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = pathname === item.href;
          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "flex flex-col items-center justify-center w-full h-full gap-1 transition-colors",
                isActive ? "text-primary" : "text-muted-foreground"
              )}
            >
              <Icon size={22} />
              <span className="text-[10px] font-medium">{item.label}</span>
            </Link>
          );
        })}
      </nav>
    </>
  );
}
