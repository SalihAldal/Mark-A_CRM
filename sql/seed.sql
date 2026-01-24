-- Mark-A CRM (multi-tenant) - seed.sql
-- Demo tenant + demo kullanıcı + demo rules + demo AI promptları

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------
-- Demo Tenant + Domains
-- -----------------------------

INSERT INTO tenants (id, name, slug, status) VALUES
(1, 'Tenant 1', 'tenant1', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), status=VALUES(status);

INSERT INTO domains (tenant_id, host, panel, is_primary, status) VALUES
(NULL, 'superadmin.localhost', 'super', 1, 'active'),
(1, 'tenant1.localhost', 'tenant', 1, 'active')
ON DUPLICATE KEY UPDATE status=VALUES(status), panel=VALUES(panel), tenant_id=VALUES(tenant_id);

-- -----------------------------
-- Roles
-- -----------------------------

INSERT INTO roles (id, tenant_id, `key`, name_tr, name_en, is_system) VALUES
(1, NULL, 'superadmin', 'Süperadmin', 'Super Admin', 1),
(2, 1, 'tenant_admin', 'Danışan (Admin)', 'Tenant Admin', 1),
(3, 1, 'staff', 'Çalışan', 'Staff', 1),
(4, 1, 'customer', 'Müşteri', 'Customer', 1)
ON DUPLICATE KEY UPDATE name_tr=VALUES(name_tr), name_en=VALUES(name_en), is_system=VALUES(is_system);

-- -----------------------------
-- Demo Users
-- password: "password"
-- -----------------------------

INSERT INTO users (id, tenant_id, role_id, name, email, password, language, timezone, status) VALUES
(1, NULL, 1, 'Super Admin', 'super@marka.local', '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'tr', 'Europe/Istanbul', 'active'),
(2, 1, 2, 'Tenant Admin', 'admin@tenant1.local', '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'tr', 'Europe/Istanbul', 'active'),
(3, 1, 3, 'Çalışan 1', 'staff@tenant1.local', '$2y$10$7rgZcQJdYNqw2EE/vsDLouoem9urL2mHIbRp4dz.YD8R0Z7M.Uj0m', 'tr', 'Europe/Istanbul', 'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), tenant_id=VALUES(tenant_id), role_id=VALUES(role_id), status=VALUES(status);

INSERT INTO user_tenants (user_id, tenant_id, role_id, status) VALUES
(1, 1, 2, 'active')
ON DUPLICATE KEY UPDATE status=VALUES(status), role_id=VALUES(role_id);

-- -----------------------------
-- Lead Stages
-- -----------------------------

INSERT INTO lead_stages (id, tenant_id, name, sort_order, color, is_won, is_lost) VALUES
(1, 1, 'Yeni', 10, '#ff7a00', 0, 0),
(2, 1, 'İletişimde', 20, '#f59e0b', 0, 0),
(3, 1, 'Teklif', 30, '#60a5fa', 0, 0),
(4, 1, 'Kazanıldı', 40, '#34d399', 1, 0),
(5, 1, 'Kaybedildi', 50, '#f87171', 0, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), sort_order=VALUES(sort_order), color=VALUES(color), is_won=VALUES(is_won), is_lost=VALUES(is_lost);

-- -----------------------------
-- Demo Contact + Lead + Thread + Messages
-- -----------------------------

INSERT INTO contacts (id, tenant_id, name, phone, email, external_id) VALUES
(1, 1, 'Ahmet Yılmaz', '+90 5xx xxx xx xx', 'ahmet@example.com', NULL)
ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), email=VALUES(email);

INSERT INTO leads (id, tenant_id, owner_user_id, assigned_user_id, contact_id, stage_id, source, status, score, name, phone, email, company, notes, tags_json, last_contact_at) VALUES
(1, 1, 2, 3, 1, 2, 'instagram', 'open', 55, 'Ahmet Yılmaz', '+90 5xx xxx xx xx', 'ahmet@example.com', 'Yılmaz İnşaat', 'İlk temas yapıldı.', JSON_ARRAY('sıcak','instagram'), NOW())
ON DUPLICATE KEY UPDATE assigned_user_id=VALUES(assigned_user_id), stage_id=VALUES(stage_id), score=VALUES(score), status=VALUES(status);

INSERT INTO threads (id, tenant_id, lead_id, contact_id, channel, integration_account_id, subject, status, last_message_at) VALUES
(1, 1, 1, 1, 'instagram', NULL, 'Instagram DM', 'open', NOW())
ON DUPLICATE KEY UPDATE status=VALUES(status), last_message_at=VALUES(last_message_at);

INSERT INTO messages (id, tenant_id, thread_id, sender_type, sender_user_id, sender_contact_id, message_type, body_text, created_at) VALUES
(1, 1, 1, 'contact', NULL, 1, 'text', 'Merhaba, fiyat alabilir miyim?', NOW() - INTERVAL 25 MINUTE),
(2, 1, 1, 'user', 3, NULL, 'text', 'Merhaba Ahmet Bey, elbette. İhtiyacınız kaç metrekare ve teslim süresi nedir?', NOW() - INTERVAL 23 MINUTE),
(3, 1, 1, 'contact', NULL, 1, 'text', 'Yaklaşık 120m2. 2 hafta içinde teslim olsun.', NOW() - INTERVAL 20 MINUTE)
ON DUPLICATE KEY UPDATE body_text=VALUES(body_text);

-- -----------------------------
-- Demo AI Rules (RULES tablosu kritik)
-- -----------------------------

INSERT INTO ai_rules (id, tenant_id, sector, tone, forbidden_phrases, sales_focus, language) VALUES
(1, 1, 'inşaat', 'profesyonel', 'rakiplerle kıyaslama yapma; aşağılayıcı/alaycı ifade kullanma', 1, 'tr')
ON DUPLICATE KEY UPDATE sector=VALUES(sector), tone=VALUES(tone), forbidden_phrases=VALUES(forbidden_phrases), sales_focus=VALUES(sales_focus), language=VALUES(language);

-- -----------------------------
-- Demo AI Prompt Templates (satış odaklı)
-- -----------------------------

INSERT INTO ai_prompt_templates (tenant_id, template_key, title, system_prompt, user_prompt, is_active) VALUES
(NULL, 'last_message_to_sale', 'Son Mesajı Satışa Bağla',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nSon mesaja satışa bağlayan, ikna edici ama baskıcı olmayan cevap yaz.', 1),
(NULL, 'objection_handle', 'İtiraz Kırma',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nMüşterinin itirazını satış lehine çevir. Net, profesyonel ve satışa götüren bir yanıt yaz.', 1),
(NULL, 'offer_generate', 'Teklif Üret',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nMüşteriye özel teklif hazırla. Kısa, anlaşılır, fiyat/teslimat/garanti gibi kritik bilgileri ekle ve bir sonraki adımı netleştir.', 1),
(NULL, 'continue_chat', 'Sohbet Devam',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nSohbeti doğal şekilde devam ettir. Bir sonraki soruyu sor ve satın almaya götürecek mikro-CTA ekle.', 1),
(NULL, 'warm_sales', 'Samimi Satış',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nDaha sıcak ama hedef odaklı mesaj yaz. Müşterinin diline göre ton ayarla.', 1),
(NULL, 'professional_sales', 'Profesyonel Satış',
'Sen {{sector}} sektöründe çalışan deneyimli bir satış uzmanısın.',
'Kurallar:\n{{rules}}\n\nSohbet:\n{{chat_history}}\n\nKurumsal ve net satış dili kullan. Kapanış için uygun bir sonraki adımı öner.', 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), system_prompt=VALUES(system_prompt), user_prompt=VALUES(user_prompt), is_active=VALUES(is_active);

-- -----------------------------
-- Demo Knowledge Base
-- -----------------------------

INSERT INTO knowledge_base_articles (tenant_id, type, title, content, language) VALUES
(1, 'knowledge', 'Sık Sorulan Sorular', 'Bu alan bilgi bankası içerikleri için tasarlanmıştır.', 'tr'),
(1, 'res_ad_copy', 'Res (Reklam metnisi) - Örnek', 'Başlık: ...\nAçıklama: ...\nCTA: ...', 'tr');

