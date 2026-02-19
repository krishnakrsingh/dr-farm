
"use client";

import { User, Settings, Bell, Heart, CreditCard, LogOut, Package, Leaf } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";

export default function ProfilePage() {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="flex items-center gap-6 p-6 bg-white rounded-3xl shadow-lg border border-primary/5">
        <div className="w-24 h-24 bg-primary text-white rounded-full flex items-center justify-center text-4xl font-bold shadow-xl border-4 border-white">
          RK
        </div>
        <div className="space-y-1">
          <h1 className="text-2xl font-bold text-primary">Rajesh Kumar</h1>
          <p className="text-muted-foreground flex items-center gap-1">
            <Package size={14} /> Verified Seller • Member since 2023
          </p>
          <div className="flex gap-2 pt-2">
            <Button size="sm" className="bg-primary hover:bg-primary/90 h-8 rounded-full">Edit Profile</Button>
            <Button size="sm" variant="outline" className="h-8 rounded-full border-primary/20">Public View</Button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="space-y-6">
          <h2 className="text-xl font-bold text-primary flex items-center gap-2">
            <Leaf className="text-accent" /> My Dashboard
          </h2>
          <Card className="border-none shadow-lg">
            <CardContent className="p-0">
              <div className="p-4 flex items-center justify-between hover:bg-secondary/50 cursor-pointer transition-colors rounded-t-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <Package size={20} />
                  </div>
                  <div>
                    <div className="font-bold">My Listings</div>
                    <div className="text-xs text-muted-foreground">3 active products in market</div>
                  </div>
                </div>
                <div className="text-primary font-bold">3</div>
              </div>
              <Separator />
              <div className="p-4 flex items-center justify-between hover:bg-secondary/50 cursor-pointer transition-colors">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <Heart size={20} />
                  </div>
                  <div>
                    <div className="font-bold">Wishlist</div>
                    <div className="text-xs text-muted-foreground">Farming tools saved for later</div>
                  </div>
                </div>
                <div className="text-primary font-bold">12</div>
              </div>
              <Separator />
              <div className="p-4 flex items-center justify-between hover:bg-secondary/50 cursor-pointer transition-colors rounded-b-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <CreditCard size={20} />
                  </div>
                  <div>
                    <div className="font-bold">Payments</div>
                    <div className="text-xs text-muted-foreground">Wallet and bank accounts</div>
                  </div>
                </div>
                <div className="text-primary font-bold">₹24,500</div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <h2 className="text-xl font-bold text-primary flex items-center gap-2">
            <Settings className="text-accent" /> App Settings
          </h2>
          <Card className="border-none shadow-lg">
            <CardContent className="p-0">
              <div className="p-4 flex items-center justify-between hover:bg-secondary/50 cursor-pointer transition-colors rounded-t-xl">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-muted rounded-xl flex items-center justify-center text-muted-foreground">
                    <Bell size={20} />
                  </div>
                  <div className="font-bold">Notifications</div>
                </div>
                <div className="text-xs text-green-600 font-bold">ON</div>
              </div>
              <Separator />
              <div className="p-4 flex items-center justify-between hover:bg-secondary/50 cursor-pointer transition-colors">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-muted rounded-xl flex items-center justify-center text-muted-foreground">
                    <Settings size={20} />
                  </div>
                  <div className="font-bold">Preferences</div>
                </div>
              </div>
              <Separator />
              <div className="p-4 flex items-center justify-between hover:bg-red-50 cursor-pointer transition-colors rounded-b-xl group">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors">
                    <LogOut size={20} />
                  </div>
                  <div className="font-bold text-red-600">Sign Out</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
