
"use client";

import Image from "next/image";
import Link from "next/link";
import { PlaceHolderImages } from "@/lib/placeholder-images";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Camera, Newspaper, ShoppingBasket, ArrowRight, Sun, Droplets, Leaf } from "lucide-react";

export default function HomePage() {
  const hero = PlaceHolderImages.find(img => img.id === "hero-farm");

  return (
    <div className="space-y-10">
      {/* Hero Section */}
      <section className="relative h-[400px] rounded-3xl overflow-hidden group">
        {hero && (
          <Image
            src={hero.imageUrl}
            alt={hero.description}
            fill
            className="object-cover transition-transform duration-700 group-hover:scale-105"
            priority
            data-ai-hint={hero.imageHint}
          />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex flex-col justify-end p-8 md:p-12">
          <h1 className="text-4xl md:text-6xl font-bold text-white mb-4">
            Empowering Farmers with <span className="text-accent">Intelligence</span>
          </h1>
          <p className="text-white/90 text-lg max-w-2xl mb-8">
            Get real-time plant diagnostics, localized farming alerts, and access to a vibrant marketplace.
          </p>
          <div className="flex flex-wrap gap-4">
            <Button asChild size="lg" className="bg-primary hover:bg-primary/90 rounded-full h-12 px-8">
              <Link href="/analysis" className="flex items-center gap-2">
                <Camera size={20} />
                Analyze Plant
              </Link>
            </Button>
            <Button asChild variant="outline" size="lg" className="bg-white/10 text-white border-white/20 hover:bg-white/20 rounded-full h-12 px-8">
              <Link href="/marketplace">Explore Market</Link>
            </Button>
          </div>
        </div>
      </section>

      {/* Quick Stats / Weather Widget */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="bg-primary text-white border-none shadow-xl">
          <CardHeader className="pb-2">
            <CardTitle className="text-lg flex items-center gap-2">
              <Sun className="text-accent" /> Local Forecast
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">28°C</div>
            <p className="text-white/80">Partly Cloudy • Humidity 65%</p>
          </CardContent>
        </Card>
        <Card className="bg-accent text-accent-foreground border-none shadow-xl">
          <CardHeader className="pb-2">
            <CardTitle className="text-lg flex items-center gap-2">
              <Leaf /> Market Trends
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">+12%</div>
            <p className="text-accent-foreground/80">Tomato prices rising this week</p>
          </CardContent>
        </Card>
        <Card className="bg-white border-none shadow-xl">
          <CardHeader className="pb-2">
            <CardTitle className="text-lg flex items-center gap-2 text-primary">
              <Droplets /> Irrigation Alert
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">Optimal</div>
            <p className="text-muted-foreground">Soil moisture is within range</p>
          </CardContent>
        </Card>
      </section>

      {/* Features Grid */}
      <section className="space-y-6">
        <h2 className="text-3xl font-bold text-primary">Explore Our Tools</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <Card className="group hover:shadow-2xl transition-all duration-300 border-none shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-4 group-hover:bg-primary group-hover:text-white transition-colors">
                <Camera size={24} />
              </div>
              <CardTitle>AI Plant Doctor</CardTitle>
              <CardDescription>
                Upload or point your camera to any plant to get instant health analysis and treatment advice.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button variant="link" asChild className="p-0 text-primary">
                <Link href="/analysis" className="flex items-center gap-2 group">
                  Try Now <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                </Link>
              </Button>
            </CardContent>
          </Card>

          <Card className="group hover:shadow-2xl transition-all duration-300 border-none shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-4 group-hover:bg-primary group-hover:text-white transition-colors">
                <Newspaper size={24} />
              </div>
              <CardTitle>Regional Alerts</CardTitle>
              <CardDescription>
                Stay updated with location-specific weather alerts, pest outbreaks, and government schemes.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button variant="link" asChild className="p-0 text-primary">
                <Link href="/news" className="flex items-center gap-2 group">
                  View Feed <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                </Link>
              </Button>
            </CardContent>
          </Card>

          <Card className="group hover:shadow-2xl transition-all duration-300 border-none shadow-lg">
            <CardHeader>
              <div className="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-4 group-hover:bg-primary group-hover:text-white transition-colors">
                <ShoppingBasket size={24} />
              </div>
              <CardTitle>Agri Market</CardTitle>
              <CardDescription>
                List your yield directly to buyers or browse local produce and farming equipment.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button variant="link" asChild className="p-0 text-primary">
                <Link href="/marketplace" className="flex items-center gap-2 group">
                  Go to Market <ArrowRight size={16} className="group-hover:translate-x-1 transition-transform" />
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </section>
    </div>
  );
}
