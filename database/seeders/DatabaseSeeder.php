<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\StaffMember;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin user (no tenant_id)
        $superAdmin = User::firstOrCreate(
            ['email' => 'contact@punchlistify.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('secret'),
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Demo tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-realty'],
            [
                'name' => 'Demo Realty Group',
                'email' => 'info@demorealty.com',
                'plan' => 'pro',
                'trial_ends_at' => now()->addDays(30),
            ]
        );

        // Tenant admin user
        $tenantUser = User::firstOrCreate(
            ['email' => 'admin@demorealty.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'password' => Hash::make('secret'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
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
                'social_youtube' => '',
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

        // Demo staff members
        $staff1 = StaffMember::firstOrCreate(
            ['email' => 'sarah@demorealty.com', 'tenant_id' => $tenant->id],
            [
                'name'       => 'Sarah Mitchell',
                'role'       => 'Lead Agent',
                'phone'      => '(555) 234-5678',
                'bio'        => 'With over 12 years of experience in residential real estate, Sarah specializes in helping first-time buyers navigate the market with confidence.',
                'status'     => 'active',
                'sort_order' => 1,
                'display_on_homepage'    => true,
                'accepts_appointments'   => true,
            ]
        );

        $staff2 = StaffMember::firstOrCreate(
            ['email' => 'james@demorealty.com', 'tenant_id' => $tenant->id],
            [
                'name'       => 'James Rodriguez',
                'role'       => 'Senior Agent',
                'phone'      => '(555) 345-6789',
                'bio'        => 'James brings a decade of commercial and luxury property expertise. Known for his market analysis skills and negotiation prowess.',
                'status'     => 'active',
                'sort_order' => 2,
                'display_on_homepage'    => true,
                'accepts_appointments'   => true,
            ]
        );

        $staff3 = StaffMember::firstOrCreate(
            ['email' => 'emily@demorealty.com', 'tenant_id' => $tenant->id],
            [
                'name'       => 'Emily Chen',
                'role'       => 'Buyer Specialist',
                'phone'      => '(555) 456-7890',
                'bio'        => 'Emily is passionate about finding the perfect match between buyers and homes. She focuses on condos and townhomes in the downtown area.',
                'status'     => 'active',
                'sort_order' => 3,
                'display_on_homepage'    => true,
                'accepts_appointments'   => true,
            ]
        );

        // Demo properties (8 total)
        Property::firstOrCreate(
            ['title' => 'Modern Lakefront Estate', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 1250000,
                'address_street'  => '1 Lakeshore Drive',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90210',
                'bedrooms'        => 5,
                'bathrooms'       => 4,
                'half_baths'      => 1,
                'square_feet'     => 4200,
                'lot_size'        => '0.85 acres',
                'year_built'      => 2019,
                'garage'          => 3,
                'has_pool'        => true,
                'has_fireplace'   => true,
                'view_count'      => 142,
                'staff_member_id' => $staff1->id,
                'description'     => 'Stunning lakefront estate with panoramic water views. Open floor plan, chef\'s kitchen with quartz countertops, and a resort-style backyard with infinity pool. Master suite with private balcony overlooking the lake.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Downtown Luxury Penthouse', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'condo',
                'price'           => 875000,
                'address_street'  => '500 Main Street',
                'address_line_2'  => 'PH-1',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90210',
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 2400,
                'year_built'      => 2021,
                'garage'          => 2,
                'has_fireplace'   => true,
                'hoa_fee'         => 650,
                'view_count'      => 98,
                'staff_member_id' => $staff2->id,
                'description'     => 'Top-floor penthouse with floor-to-ceiling windows and 360-degree city views. Designer finishes throughout, private elevator access, and rooftop terrace. Walking distance to restaurants and nightlife.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Charming Craftsman Bungalow', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 425000,
                'address_street'  => '742 Maple Street',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90211',
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 1650,
                'year_built'      => 1928,
                'garage'          => 1,
                'has_fireplace'   => true,
                'has_basement'    => true,
                'near_school'     => true,
                'view_count'      => 67,
                'staff_member_id' => $staff1->id,
                'description'     => 'Original Craftsman character meets modern updates. Hardwood floors, built-in bookshelves, and a wrap-around porch. Updated kitchen and bathrooms. Mature landscaping with detached garage studio.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Cozy Studio — Downtown', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'condo',
                'price'           => 189000,
                'address_street'  => '12 Oak Ave',
                'address_line_2'  => '#4B',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90210',
                'bedrooms'        => 1,
                'bathrooms'       => 1,
                'square_feet'     => 580,
                'year_built'      => 2015,
                'hoa_fee'         => 275,
                'near_shopping'   => true,
                'near_transit'    => true,
                'view_count'      => 45,
                'staff_member_id' => $staff3->id,
                'description'     => 'Efficient and stylish downtown studio. In-unit washer/dryer, modern finishes, and a Juliet balcony. Building amenities include gym, rooftop lounge, and bike storage. Walk score 95.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Family Home with Pool', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'pending',
                'property_type'   => 'house',
                'price'           => 549000,
                'address_street'  => '2200 Sunset Boulevard',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90212',
                'bedrooms'        => 4,
                'bathrooms'       => 3,
                'square_feet'     => 2800,
                'lot_size'        => '0.35 acres',
                'year_built'      => 2005,
                'garage'          => 2,
                'has_pool'        => true,
                'has_fireplace'   => true,
                'near_school'     => true,
                'near_shopping'   => true,
                'view_count'      => 31,
                'staff_member_id' => $staff2->id,
                'description'     => 'Spacious family home on a quiet cul-de-sac. Open concept living, gourmet kitchen, and large backyard with saltwater pool and built-in BBQ. Top-rated school district.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Historic Victorian', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'sold',
                'property_type'   => 'house',
                'price'           => 725000,
                'address_street'  => '88 Heritage Lane',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90210',
                'bedrooms'        => 4,
                'bathrooms'       => 3,
                'half_baths'      => 1,
                'square_feet'     => 3100,
                'year_built'      => 1895,
                'garage'          => 1,
                'has_fireplace'   => true,
                'has_basement'    => true,
                'view_count'      => 210,
                'staff_member_id' => $staff1->id,
                'description'     => 'Beautifully restored Victorian with original millwork, stained glass, and three fireplaces. Modern systems throughout. Carriage house could be converted to ADU. National Register eligible.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'New Construction Townhome', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'townhouse',
                'price'           => 389000,
                'address_street'  => '15 Park Row',
                'address_line_2'  => 'Unit C',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90211',
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'half_baths'      => 1,
                'square_feet'     => 1900,
                'year_built'      => 2026,
                'garage'          => 2,
                'hoa_fee'         => 185,
                'near_transit'    => true,
                'view_count'      => 22,
                'staff_member_id' => $staff3->id,
                'description'     => 'Brand new construction with smart home technology throughout. Energy-efficient design with solar panels, EV charger, and tankless water heater. Private patio and attached two-car garage.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'New Listing — Pending Photos', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 349000,
                'address_street'  => '88 Elm Court',
                'address_city'    => 'Anytown',
                'address_state'   => 'CA',
                'address_zip'     => '90210',
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 1500,
                'year_built'      => 1985,
                'garage'          => 1,
                'view_count'      => 0,
                'description'     => 'Just listed! Well-maintained ranch home in established neighborhood. Photos and virtual tour coming soon. Schedule a private showing today.',
            ]
        );

        $this->command->info('✓ Super admin: contact@punchlistify.com / secret');
        $this->command->info('✓ Demo tenant: demo-realty (admin@demorealty.com / secret)');
        $this->command->info('✓ 3 staff members, 8 properties, settings, and legal pages created');
    }

    private function privacyPolicyTemplate(string $name): string
    {
        $date = now()->format('F j, Y');
        return <<<EOT
Privacy Policy

Last updated: {$date}

1. Introduction
{$name} ("we", "us", or "our") operates this website and is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.

2. Our Privacy Commitment
Your privacy is not just a legal obligation to us — it is a core value. We will never sell, rent, trade, or otherwise share your personal information with third parties for their own marketing or commercial purposes. Ever. The information you provide is used solely to serve you and respond to your inquiries.

3. Information We Collect
We may collect the following types of personal information:
- Contact information: name, email address, phone number
- Inquiry and appointment details you submit through our forms
- Usage data: pages visited, browser type, IP address (via cookies and analytics tools)
- Communications you send to us via chat, email, or contact forms

4. How We Use Your Information
We use the information we collect exclusively to:
- Respond to your inquiries and schedule appointments
- Send you information about properties and our services that you have requested
- Improve our website and user experience
- Comply with legal obligations

We do not use your information for any other purpose without your explicit consent.

5. We Will Never Sell Your Data
We do not sell, rent, license, or trade your personal information to any third party. This includes data brokers, advertisers, and marketing companies. Your contact details exist solely to help us serve you.

6. Cookies and Tracking Technologies
We use cookies and similar technologies to analyze website traffic and improve your experience. You may decline non-essential cookies using the consent banner on our site. Declining cookies will not prevent you from using the site.

7. Third-Party Services
We may use limited third-party services such as Google Analytics solely to understand how our website is used in aggregate. These services operate under their own privacy policies. We do not share your personal contact information with these services.

8. Data Retention
We retain personal information only as long as necessary to fulfill the purposes described in this policy or as required by law.

9. Your Rights
Depending on your location, you may have the right to:
- Access the personal data we hold about you
- Request correction or deletion of your data
- Opt out of marketing communications
- Lodge a complaint with a supervisory authority (EU/UK residents)
- Request data deletion (California residents under CCPA)

To exercise any of these rights, please contact us using the information below.

10. Data Security
We implement reasonable technical and organizational measures to protect your personal information. However, no transmission over the internet is completely secure.

11. Children's Privacy
Our services are not directed to individuals under 18. We do not knowingly collect personal information from children.

12. Changes to This Policy
We may update this Privacy Policy from time to time. We will notify you of changes by updating the date at the top of this page.

13. Contact Us
If you have questions about this Privacy Policy or wish to exercise your rights, please contact us through the contact form on our website.
EOT;
    }

    private function termsTemplate(string $name): string
    {
        $date = now()->format('F j, Y');
        return <<<EOT
Terms of Service

Last updated: {$date}

1. Acceptance of Terms
By accessing or using the website operated by {$name} ("we", "us", "our"), you agree to be bound by these Terms of Service. If you do not agree, please do not use this site.

2. Use of Service
You agree to use this website only for lawful purposes and in a manner that does not infringe the rights of others. You may not use this site to:
- Transmit unlawful, harassing, or fraudulent content
- Attempt to gain unauthorized access to any part of the site
- Scrape or harvest listing data for commercial purposes

3. Property Listings and Information
All property listings, descriptions, and pricing information are provided for informational purposes only. Information is deemed reliable but not guaranteed. We make no warranty as to the accuracy, completeness, or timeliness of listing data.

4. Fair Housing
We are committed to the principles of the Fair Housing Act. We do not discriminate on the basis of race, color, national origin, religion, sex, familial status, disability, or any other protected class.

5. No Legal or Financial Advice
Nothing on this website constitutes legal, financial, tax, or investment advice. Consult a licensed professional before making any real estate decisions.

6. Intellectual Property
All content on this website, including text, images, and design elements, is the property of {$name} or its licensors and may not be reproduced without written permission.

7. Third-Party Links
Our website may contain links to third-party websites. We are not responsible for the content or privacy practices of those sites.

8. Limitation of Liability
To the fullest extent permitted by law, {$name} shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of this website or reliance on any information provided herein.

9. Disclaimer of Warranties
This website is provided "as is" without warranties of any kind, either express or implied, including but not limited to implied warranties of merchantability or fitness for a particular purpose.

10. Governing Law
These Terms shall be governed by and construed in accordance with the laws of the state in which {$name} operates, without regard to its conflict of law provisions.

11. Changes to Terms
We reserve the right to modify these Terms at any time. Continued use of the site after changes constitutes acceptance of the revised Terms.

12. Contact Us
If you have questions about these Terms, please contact us through the contact form on our website.
EOT;
    }
}
