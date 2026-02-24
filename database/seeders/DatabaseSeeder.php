<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SiteSettings;
use App\Models\LegalPage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin user (no tenant_id)
        $superAdmin = User::firstOrCreate(
            ['email' => 'contact@punchlistify.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('secret'),
                'is_super_admin' => true,
                
            ]
        );

        // Demo tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-realty'],
            [
                'name' => 'Demo Realty Group',
                'email' => 'info@demorealty.com',
                'password' => Hash::make('secret'),
                'plan' => 'pro',
                'trial_ends_at' => now()->addDays(30),
            ]
        );

        // Tenant admin user
        $tenantUser = User::firstOrCreate(
            ['email' => 'admin@demorealty.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('secret'),
                'tenant_id' => $tenant->id,
                
            ]
        );

        // Default site settings for demo tenant
        SiteSettings::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'site_title' => 'Demo Realty Group',
                'tagline' => 'Finding Your Dream Home',
                'primary_color' => '#2563eb',
                'header_mode' => 'hero',
                'header_display_mode' => 'text_only',
                'title_font' => 'Poppins',
                'site_title_font_size' => '3xl',
                'site_title_font_weight' => '700',
                'site_title_letter_spacing' => 'normal',
                'title_color_type' => 'gradient',
                'title_gradient_start' => '#2563eb',
                'title_gradient_via' => '#7c3aed',
                'title_gradient_end' => '#db2777',
                'hero_background_type' => 'preset',
                'hero_preset' => 'modern',
                'dark_mode_enabled' => true,
                'chatbot_enabled' => false,
                'chatbot_personality' => 'professional',
                'notify_on_contact' => true,
                'notify_on_appointment' => true,
                'homepage_sections' => [
                    ['key' => 'hero', 'enabled' => true, 'order' => 1],
                    ['key' => 'features', 'enabled' => true, 'order' => 2],
                    ['key' => 'listings', 'enabled' => true, 'order' => 3],
                    ['key' => 'stats', 'enabled' => true, 'order' => 4],
                    ['key' => 'services', 'enabled' => true, 'order' => 5],
                    ['key' => 'team', 'enabled' => true, 'order' => 6],
                    ['key' => 'testimonials', 'enabled' => true, 'order' => 7],
                    ['key' => 'faq', 'enabled' => true, 'order' => 8],
                    ['key' => 'contact', 'enabled' => true, 'order' => 9],
                ],
                'features_items' => [
                    ['icon' => 'home', 'title' => 'Residential Expertise', 'description' => 'Years of experience helping families find their perfect home.'],
                    ['icon' => 'building', 'title' => 'Commercial Properties', 'description' => 'From office spaces to retail — we cover all commercial needs.'],
                    ['icon' => 'chart-bar', 'title' => 'Market Analysis', 'description' => 'Data-driven insights to help you make informed decisions.'],
                    ['icon' => 'shield-check', 'title' => 'Trusted Service', 'description' => 'Hundreds of satisfied clients and 5-star reviews.'],
                ],
                'services_items' => [
                    ['icon' => 'home', 'title' => 'Home Buying', 'description' => 'Expert guidance through every step of the buying process.'],
                    ['icon' => 'currency-dollar', 'title' => 'Home Selling', 'description' => 'Get maximum value for your property with our proven strategies.'],
                    ['icon' => 'key', 'title' => 'Property Management', 'description' => 'Hassle-free management for rental property owners.'],
                ],
                'stats_items' => [
                    ['value' => '500+', 'label' => 'Homes Sold'],
                    ['value' => '15+', 'label' => 'Years Experience'],
                    ['value' => '98%', 'label' => 'Client Satisfaction'],
                    ['value' => '$2B+', 'label' => 'Total Sales Volume'],
                ],
                'testimonials_items' => [
                    ['name' => 'Sarah Johnson', 'rating' => 5, 'text' => 'The team was incredible! They found us our dream home in under 3 weeks. Highly recommend!'],
                    ['name' => 'Mike & Lisa Chen', 'rating' => 5, 'text' => 'Professional, responsive, and truly cared about getting us the best deal. Outstanding service.'],
                    ['name' => 'Robert Williams', 'rating' => 5, 'text' => 'Sold my home for 15% above asking price. Could not be happier with the results.'],
                ],
                'faq_items' => [
                    ['question' => 'How do I get started buying a home?', 'answer' => 'Contact us for a free consultation. We\'ll discuss your needs, budget, and timeline to create a personalized plan.'],
                    ['question' => 'What areas do you serve?', 'answer' => 'We serve the entire metro area and surrounding suburbs. Contact us to discuss your specific location needs.'],
                    ['question' => 'How long does the buying process take?', 'answer' => 'Typically 30-60 days from offer acceptance to closing, depending on financing and inspection results.'],
                    ['question' => 'Do you charge buyers a fee?', 'answer' => 'In most cases, buyer representation is free to you — the seller pays both agents\' commissions.'],
                ],
                'hero_title' => 'Find Your Dream Home',
                'hero_subtitle' => 'Trusted real estate experts helping families find the perfect property. Browse our listings and start your journey today.',
                'cta_primary_text' => 'Browse Listings',
                'cta_primary_link' => '/gallery',
                'cta_secondary_text' => 'Contact Us',
                'cta_secondary_link' => '#contact',
                'contact_email' => 'info@demorealty.com',
                'contact_phone' => '(555) 123-4567',
                'contact_address' => '123 Main Street, Anytown, USA 12345',
                'social_facebook' => '',
                'social_instagram' => '',
                'social_twitter' => '',
                'social_linkedin' => '',
                'dashboard_config' => [
                    'stats' => ['active_listings', 'unread_messages', 'appointments', 'portfolio_value'],
                    'charts' => ['properties_by_type', 'properties_by_status', 'views_per_day'],
                    'tables' => ['recent_messages', 'upcoming_appointments', 'top_properties'],
                ],
            ]
        );

        // Default legal pages
        LegalPage::firstOrCreate(
            ['tenant_id' => $tenant->id, 'page_type' => 'privacy'],
            [
                'content' => $this->privacyPolicyTemplate($tenant->name),
            ]
        );

        LegalPage::firstOrCreate(
            ['tenant_id' => $tenant->id, 'page_type' => 'terms'],
            [
                'content' => $this->termsTemplate($tenant->name),
            ]
        );

        $this->command->info('✓ Super admin: contact@punchlistify.com / secret');
        $this->command->info('✓ Demo tenant: demo-realty (admin@demorealty.com / secret)');
        $this->command->info('✓ Default settings and legal pages created');
    }

    private function privacyPolicyTemplate(string $name): string
    {
        return "Privacy Policy\n\nLast updated: " . now()->format('F j, Y') . "\n\n$name (\"we\", \"us\", or \"our\") operates this website. This page informs you of our policies regarding the collection, use, and disclosure of personal information we receive from users.\n\nInformation Collection and Use\nWe collect several different types of information for various purposes to provide and improve our service to you, including contact information you provide through our forms.\n\nData Security\nThe security of your data is important to us. We strive to use commercially acceptable means to protect your Personal Information.\n\nContact Us\nIf you have any questions about this Privacy Policy, please contact us.";
    }

    private function termsTemplate(string $name): string
    {
        return "Terms of Service\n\nLast updated: " . now()->format('F j, Y') . "\n\nPlease read these Terms of Service carefully before using our website operated by $name.\n\nBy accessing or using our service, you agree to be bound by these Terms.\n\nUse of Service\nYou agree to use our services only for lawful purposes and in accordance with these Terms.\n\nContact Us\nIf you have any questions about these Terms, please contact us.";
    }
}
