
"use client";

import { useState, useMemo } from "react";
import { Newspaper, Bell, MapPin, CloudRain, Sun, Zap, Info, Filter } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { cn } from "@/lib/utils";

const ALERTS = [
  {
    id: 1,
    type: "Weather",
    title: "Heavy Rainfall Expected",
    location: "Punjab, North Region",
    region: "north",
    description: "Forecast predicts 50mm+ rainfall in the next 48 hours. Ensure proper drainage in low-lying wheat fields.",
    severity: "High",
    icon: CloudRain,
    date: "2 hours ago",
  },
  {
    id: 2,
    type: "Pest",
    title: "Locust Swarm Warning",
    location: "Rajasthan Border Area",
    region: "west",
    description: "Sightings reported near Barmer. Contact agricultural extension office for preventative spray kits.",
    severity: "Critical",
    icon: Zap,
    date: "5 hours ago",
  },
  {
    id: 3,
    type: "Policy",
    title: "New Subsidy for Solar Pumps",
    location: "Central India",
    region: "central",
    description: "Pradhan Mantri KUSUM scheme applications are now open. Up to 60% subsidy for small-scale farmers.",
    severity: "Informational",
    icon: Info,
    date: "Yesterday",
  },
  {
    id: 4,
    type: "Market",
    title: "Onion Market Surplus",
    location: "Maharashtra Region",
    region: "west",
    description: "High supply expected in Nashik Mandi. Consider cold storage or delayed harvest to avoid price dips.",
    severity: "Medium",
    icon: Sun,
    date: "Yesterday",
  },
];

export default function NewsPage() {
  const [region, setRegion] = useState("all");

  const filteredAlerts = useMemo(() => {
    if (region === "all") return ALERTS;
    return ALERTS.filter((alert) => alert.region === region);
  }, [region]);

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-primary flex items-center gap-2">
            <Newspaper /> Farming Alerts & News
          </h1>
          <p className="text-muted-foreground flex items-center gap-1">
            <MapPin size={16} /> Showing updates for {region === "all" ? "all regions" : `${region} region`}
          </p>
        </div>

        <div className="flex items-center gap-2 w-full md:w-auto">
          <Select value={region} onValueChange={setRegion}>
            <SelectTrigger className="w-full md:w-[200px] bg-white rounded-full">
              <Filter className="mr-2 h-4 w-4" />
              <SelectValue placeholder="Select Region" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All India</SelectItem>
              <SelectItem value="north">North Region</SelectItem>
              <SelectItem value="west">West Region</SelectItem>
              <SelectItem value="south">South Region</SelectItem>
              <SelectItem value="central">Central India</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredAlerts.map((alert) => {
          const Icon = alert.icon;
          return (
            <Card key={alert.id} className="border-none shadow-lg overflow-hidden group hover:shadow-xl transition-all">
              <div className={cn(
                "h-2",
                alert.severity === "Critical" ? "bg-destructive" :
                  alert.severity === "High" ? "bg-orange-500" :
                    alert.severity === "Medium" ? "bg-accent" : "bg-primary"
              )} />
              <CardHeader>
                <div className="flex justify-between items-start mb-2">
                  <Badge variant="secondary" className="bg-primary/5 text-primary">
                    {alert.type}
                  </Badge>
                  <span className="text-xs text-muted-foreground">{alert.date}</span>
                </div>
                <CardTitle className="text-xl group-hover:text-primary transition-colors">{alert.title}</CardTitle>
                <CardDescription className="flex items-center gap-1 text-primary/70 font-medium">
                  <MapPin size={14} /> {alert.location}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex gap-4">
                  <div className="shrink-0 w-12 h-12 bg-secondary rounded-2xl flex items-center justify-center text-primary">
                    <Icon size={24} />
                  </div>
                  <p className="text-sm text-muted-foreground leading-relaxed">
                    {alert.description}
                  </p>
                </div>
              </CardContent>
              <CardFooter className="bg-muted/30 py-3">
                <Button variant="link" className="p-0 text-primary h-auto font-bold ml-auto">
                  View Full Report
                </Button>
              </CardFooter>
            </Card>
          );
        })}
      </div>

      {filteredAlerts.length === 0 && (
        <div className="text-center py-20">
          <Newspaper size={64} className="mx-auto text-primary/10 mb-4" />
          <h3 className="text-2xl font-bold text-primary">No alerts for this region</h3>
          <p className="text-muted-foreground">Try selecting a different region or check back later.</p>
        </div>
      )}

      <Card className="bg-primary text-white border-none shadow-2xl overflow-hidden relative">
        <div className="absolute right-0 top-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2" />
        <CardContent className="p-8 md:p-12 relative">
          <h2 className="text-3xl font-bold mb-4">Never miss an update</h2>
          <p className="text-white/80 max-w-xl mb-6">
            Get instant push notifications for critical pest warnings and weather anomalies in your specific PIN code.
          </p>
          <Button size="lg" className="bg-accent hover:bg-accent/90 text-accent-foreground rounded-full h-12 px-8">
            <Bell className="mr-2" size={20} /> Enable Smart Notifications
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
