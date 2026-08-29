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
            ['email' => 'contact@busyrealtor.com'],
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

        // Demo properties (8 total — Golden Isles, Georgia)
        Property::firstOrCreate(
            ['title' => 'Oceanfront Estate on St. Simons', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 1495000,
                'address_street'  => '128 Ocean Boulevard',
                'address_city'    => 'St. Simons Island',
                'address_state'   => 'GA',
                'address_zip'     => '31522',
                'latitude'        => 31.1536,
                'longitude'       => -81.3695,
                'bedrooms'        => 5,
                'bathrooms'       => 4,
                'half_baths'      => 1,
                'square_feet'     => 4500,
                'lot_size'        => '0.75 acres',
                'year_built'      => 2018,
                'garage'          => 3,
                'has_pool'        => true,
                'has_fireplace'   => true,
                'view_count'      => 187,
                'staff_member_id' => $staff1->id,
                'description'     => 'Breathtaking oceanfront estate with unobstructed views of the Atlantic. Custom-built with tabby accents, chef\'s kitchen, and a screened veranda spanning the entire rear. Heated saltwater pool, outdoor kitchen, and private beach access.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Historic Cottage in Village', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 625000,
                'address_street'  => '412 Mallery Street',
                'address_city'    => 'St. Simons Island',
                'address_state'   => 'GA',
                'address_zip'     => '31522',
                'latitude'        => 31.1325,
                'longitude'       => -81.3855,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 1850,
                'year_built'      => 1942,
                'garage'          => 1,
                'has_fireplace'   => true,
                'near_shopping'   => true,
                'view_count'      => 94,
                'staff_member_id' => $staff3->id,
                'description'     => 'Charming island cottage steps from the Village shops and pier. Original hardwood floors, updated kitchen with shaker cabinets, and a wraparound porch under ancient live oaks. Walk to restaurants, lighthouse, and beach.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Jekyll Island Marsh-View Retreat', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 485000,
                'address_street'  => '55 Riverview Drive',
                'address_city'    => 'Jekyll Island',
                'address_state'   => 'GA',
                'address_zip'     => '31527',
                'latitude'        => 31.0715,
                'longitude'       => -81.4135,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 2100,
                'lot_size'        => '0.45 acres',
                'year_built'      => 2008,
                'garage'          => 2,
                'has_fireplace'   => true,
                'near_school'     => false,
                'view_count'      => 63,
                'staff_member_id' => $staff2->id,
                'description'     => 'Elevated marsh-front home with stunning sunset views over the Intracoastal Waterway. Vaulted ceilings, screened porch with ceiling fans, and a private dock. Minutes from Driftwood Beach and the Jekyll Island Club.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Downtown Brunswick Bungalow', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 229000,
                'address_street'  => '1611 Newcastle Street',
                'address_city'    => 'Brunswick',
                'address_state'   => 'GA',
                'address_zip'     => '31520',
                'latitude'        => 31.1499,
                'longitude'       => -81.4915,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 1400,
                'year_built'      => 1925,
                'has_fireplace'   => true,
                'has_basement'    => false,
                'near_shopping'   => true,
                'near_transit'    => true,
                'view_count'      => 41,
                'staff_member_id' => $staff1->id,
                'description'     => 'Renovated bungalow in Brunswick\'s historic Old Town. Original heart pine floors, updated plumbing and electrical, and a fenced backyard with mature pecan trees. Walk to Rialto Square and the farmers market.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Waterfront Condo at Golden Isles Marina', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'pending',
                'property_type'   => 'condo',
                'price'           => 359000,
                'address_street'  => '200 Marina Drive',
                'address_line_2'  => 'Unit 304',
                'address_city'    => 'Brunswick',
                'address_state'   => 'GA',
                'address_zip'     => '31520',
                'latitude'        => 31.1460,
                'longitude'       => -81.4960,
                'bedrooms'        => 2,
                'bathrooms'       => 2,
                'square_feet'     => 1200,
                'year_built'      => 2016,
                'garage'          => 1,
                'hoa_fee'         => 425,
                'view_count'      => 38,
                'staff_member_id' => $staff2->id,
                'description'     => 'Turn-key marina condo with boat slip included. Open floor plan, granite countertops, and a balcony overlooking the harbor. Amenities include pool, fitness center, and kayak launch. Easy access to St. Simons Causeway.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Plantation-Style Home on Sea Island Road', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'sold',
                'property_type'   => 'house',
                'price'           => 875000,
                'address_street'  => '3200 Sea Island Road',
                'address_city'    => 'St. Simons Island',
                'address_state'   => 'GA',
                'address_zip'     => '31522',
                'latitude'        => 31.1680,
                'longitude'       => -81.3780,
                'bedrooms'        => 4,
                'bathrooms'       => 3,
                'half_baths'      => 1,
                'square_feet'     => 3400,
                'lot_size'        => '1.2 acres',
                'year_built'      => 2001,
                'garage'          => 2,
                'has_pool'        => true,
                'has_fireplace'   => true,
                'view_count'      => 245,
                'staff_member_id' => $staff1->id,
                'description'     => 'Stately low-country home on a secluded lot with mature live oaks and Spanish moss. Grand foyer, formal dining, and a keeping room off the kitchen. Screened pool and outdoor fireplace. Minutes from Sea Island Resort.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'New Construction Townhome — Glynn Place', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'townhouse',
                'price'           => 289000,
                'address_street'  => '45 Glynn Avenue',
                'address_line_2'  => 'Unit B',
                'address_city'    => 'Brunswick',
                'address_state'   => 'GA',
                'address_zip'     => '31520',
                'latitude'        => 31.1550,
                'longitude'       => -81.4850,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'half_baths'      => 1,
                'square_feet'     => 1750,
                'year_built'      => 2026,
                'garage'          => 2,
                'hoa_fee'         => 195,
                'near_shopping'   => true,
                'near_transit'    => true,
                'view_count'      => 19,
                'staff_member_id' => $staff3->id,
                'description'     => 'Brand new energy-efficient townhome with smart home features. Open concept with 10-foot ceilings, quartz counters, and LVP flooring throughout. Private fenced patio and attached two-car garage. Close to shopping and I-95.',
            ]
        );

        Property::firstOrCreate(
            ['title' => 'Beachside Bungalow — East Beach', 'tenant_id' => $tenant->id],
            [
                'listing_status'  => 'active',
                'property_type'   => 'house',
                'price'           => 545000,
                'address_street'  => '78 First Street',
                'address_city'    => 'St. Simons Island',
                'address_state'   => 'GA',
                'address_zip'     => '31522',
                'latitude'        => 31.1410,
                'longitude'       => -81.3640,
                'bedrooms'        => 3,
                'bathrooms'       => 2,
                'square_feet'     => 1650,
                'year_built'      => 1960,
                'garage'          => 0,
                'has_fireplace'   => false,
                'near_school'     => true,
                'near_shopping'   => true,
                'view_count'      => 0,
                'staff_member_id' => $staff2->id,
                'description'     => 'Original East Beach cottage with endless potential. Two blocks from the beach, zoned for short-term rental. Updated roof and HVAC, original pine floors. Large lot with room to expand or add a guest house.',
            ]
        );

        $this->command->info('✓ Super admin: contact@busyrealtor.com / secret');
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
