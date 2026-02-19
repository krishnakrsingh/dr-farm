
"use client";

import { useState, useCallback } from "react";
import Image from "next/image";
import { ShoppingBasket, Plus, Search, MapPin, ArrowUpRight, TrendingUp } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogClose } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { PlaceHolderImages } from "@/lib/placeholder-images";
import { useToast } from "@/hooks/use-toast";

type Listing = {
  id: number;
  title: string;
  price: string;
  seller: string;
  location: string;
  category: string;
  image?: string;
  quantity: string;
};

const INITIAL_LISTINGS: Listing[] = [
  {
    id: 1,
    title: "Organic Red Tomatoes",
    price: "₹40/kg",
    seller: "Rajesh Kumar",
    location: "Nashik, Maharashtra",
    category: "Vegetables",
    image: PlaceHolderImages.find(img => img.id === "healthy-tomato")?.imageUrl,
    quantity: "500kg available",
  },
  {
    id: 2,
    title: "Golden Wheat Grains",
    price: "₹2500/quintal",
    seller: "Sukhwinder Singh",
    location: "Ludhiana, Punjab",
    category: "Grains",
    image: PlaceHolderImages.find(img => img.id === "wheat-harvest")?.imageUrl,
    quantity: "10 tons available",
  },
  {
    id: 3,
    title: "Farm Fresh Corn",
    price: "₹20/cob",
    seller: "Anitha Reddy",
    location: "Guntur, Andhra Pradesh",
    category: "Cereals",
    image: PlaceHolderImages.find(img => img.id === "corn-field")?.imageUrl,
    quantity: "2000 units",
  },
];

export default function MarketplacePage() {
  const [listings, setListings] = useState<Listing[]>(INITIAL_LISTINGS);
  const [search, setSearch] = useState("");
  const [dialogOpen, setDialogOpen] = useState(false);
  const { toast } = useToast();

  // Form state
  const [newTitle, setNewTitle] = useState("");
  const [newPrice, setNewPrice] = useState("");
  const [newCategory, setNewCategory] = useState("");
  const [newDescription, setNewDescription] = useState("");

  const filteredListings = listings.filter(item =>
    item.title.toLowerCase().includes(search.toLowerCase()) ||
    item.location.toLowerCase().includes(search.toLowerCase()) ||
    item.category.toLowerCase().includes(search.toLowerCase())
  );

  const handleSubmitListing = useCallback(() => {
    if (!newTitle.trim() || !newPrice.trim()) {
      toast({
        title: "Missing fields",
        description: "Please fill in at least the title and price.",
        variant: "destructive",
      });
      return;
    }

    const newListing: Listing = {
      id: Date.now(),
      title: newTitle.trim(),
      price: newPrice.trim(),
      seller: "You",
      location: "Your Location",
      category: newCategory.trim() || "General",
      quantity: "Available",
    };

    setListings((prev) => [newListing, ...prev]);
    setDialogOpen(false);

    // Reset form
    setNewTitle("");
    setNewPrice("");
    setNewCategory("");
    setNewDescription("");

    toast({
      title: "Listing published!",
      description: `"${newListing.title}" has been added to the marketplace.`,
    });
  }, [newTitle, newPrice, newCategory, newDescription, toast]);

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-primary flex items-center gap-2">
            <ShoppingBasket /> Farmer Marketplace
          </h1>
          <p className="text-muted-foreground">Direct trade between producers and buyers</p>
        </div>

        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button className="bg-primary hover:bg-primary/90 rounded-full h-12 px-6">
              <Plus size={20} className="mr-2" /> List Your Yield
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[425px] rounded-3xl">
            <DialogHeader>
              <DialogTitle className="text-2xl font-bold text-primary">New Listing</DialogTitle>
            </DialogHeader>
            <div className="grid gap-6 py-4">
              <div className="grid gap-2">
                <Label htmlFor="title">Produce Title *</Label>
                <Input
                  id="title"
                  placeholder="e.g. Fresh Organic Carrots"
                  value={newTitle}
                  onChange={(e) => setNewTitle(e.target.value)}
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="price">Price (per unit) *</Label>
                  <Input
                    id="price"
                    placeholder="₹50/kg"
                    value={newPrice}
                    onChange={(e) => setNewPrice(e.target.value)}
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="category">Category</Label>
                  <Input
                    id="category"
                    placeholder="Vegetables"
                    value={newCategory}
                    onChange={(e) => setNewCategory(e.target.value)}
                  />
                </div>
              </div>
              <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  placeholder="Describe the quality and harvesting date..."
                  value={newDescription}
                  onChange={(e) => setNewDescription(e.target.value)}
                />
              </div>
              <div className="grid gap-2">
                <Label>Upload Photo</Label>
                <div className="border-2 border-dashed border-primary/20 rounded-xl p-8 text-center bg-muted/20 cursor-pointer hover:border-primary/40 transition-colors">
                  <Plus className="mx-auto text-primary/40 mb-2" />
                  <span className="text-sm text-muted-foreground">Drag & drop photo here</span>
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button
                type="button"
                onClick={handleSubmitListing}
                className="w-full bg-primary h-12 rounded-xl"
              >
                Publish Listing
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <div className="relative">
        <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground" size={20} />
        <Input
          className="pl-12 h-14 bg-white border-none shadow-md rounded-2xl focus-visible:ring-primary"
          placeholder="Search produce, locations or categories..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        {filteredListings.map((item) => (
          <Card key={item.id} className="border-none shadow-xl overflow-hidden rounded-3xl group flex flex-col">
            <div className="relative h-56">
              {item.image ? (
                <Image
                  src={item.image}
                  alt={item.title}
                  fill
                  className="object-cover group-hover:scale-105 transition-transform duration-500"
                />
              ) : (
                <div className="w-full h-full bg-gradient-to-br from-primary/10 to-accent/20 flex items-center justify-center">
                  <ShoppingBasket size={48} className="text-primary/30" />
                </div>
              )}
              <div className="absolute top-4 right-4">
                <Badge className="bg-white/90 text-primary hover:bg-white backdrop-blur-sm border-none px-3 py-1 font-bold">
                  {item.price}
                </Badge>
              </div>
              <div className="absolute bottom-4 left-4">
                <Badge variant="secondary" className="bg-primary text-white border-none">
                  {item.category}
                </Badge>
              </div>
            </div>
            <CardHeader className="pb-2">
              <CardTitle className="text-xl group-hover:text-primary transition-colors">{item.title}</CardTitle>
              <div className="flex items-center gap-1 text-muted-foreground text-sm font-medium">
                <MapPin size={14} className="text-primary" /> {item.location}
              </div>
            </CardHeader>
            <CardContent className="flex-grow">
              <div className="flex items-center gap-2 mb-4">
                <div className="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-primary text-xs font-bold">
                  {item.seller.charAt(0)}
                </div>
                <div className="text-sm font-semibold text-primary/80">{item.seller}</div>
              </div>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <TrendingUp size={14} className="text-accent" /> {item.quantity}
              </div>
            </CardContent>
            <CardFooter className="pt-0 pb-6 px-6">
              <Button className="w-full bg-white text-primary border-2 border-primary/20 hover:bg-primary hover:text-white hover:border-primary rounded-xl h-11 flex items-center justify-center gap-2 transition-all group/btn font-bold">
                Contact Seller <ArrowUpRight size={18} className="group-hover/btn:translate-x-1 group-hover/btn:-translate-y-1 transition-transform" />
              </Button>
            </CardFooter>
          </Card>
        ))}
      </div>

      {filteredListings.length === 0 && (
        <div className="text-center py-20">
          <ShoppingBasket size={64} className="mx-auto text-primary/10 mb-4" />
          <h3 className="text-2xl font-bold text-primary">No matching listings found</h3>
          <p className="text-muted-foreground">Try adjusting your search filters or browse other categories.</p>
        </div>
      )}
    </div>
  );
}
