# Trackly

[English Version](README.md)

Trackly, WordPress için modern, şık ve hafif bir Google Analytics 4 (GA4) kontrol paneli, sayfa düzeyinde istatistik istemcisi, yerel tıklama ısı haritası (heatmap) takipçisi ve özel etkinlik (event) sihirbazıdır. Site sahipleri, geliştiriciler ve pazarlamacılar için WordPress yönetim panelinden ayrılmadan doğrudan analitik içgörüler sunmak üzere tasarlanmıştır.

---

## Önemli Özellikler

- **Şık GA4 Kontrol Paneli:** Sayfa görüntüleme, tekil ziyaretçi, hemen çıkma oranları ve ortalama oturum süresi gibi metrikleri şık grafiklerle görüntüleyin.
- **Trafik ve Cihaz Analizi:** Ziyaretçilerinizin nereden geldiğini (yönlendiriciler, doğrudan, organik arama) ve hangi cihazları kullandıklarını (masaüstü, mobil, tablet) belirleyin.
- **Sayfa Düzeyinde İstatistikler:** Yöneticilerin aktif sayfaya ait istatistikleri doğrudan görüntülemesi için ön yüzde modern bir cam tasarımı (glassmorphism) panel belirir.
- **Yerel Tıklama Isı Haritaları:** Üçüncü taraf ısı haritası betiklerine ihtiyaç duymadan ziyaretçilerin sayfalarınızda nereye tıkladığını görsel olarak takip edin.
- **GA4 Özel Etkinlik Sihirbazı:** Sayfa düzenindeki butonları veya bağlantıları doğrudan seçerek etkileşimli bir şekilde özel Google Analytics takip etkinlikleri oluşturun.
- **Yapay Zeka Destekli İçgörüler:** Trafik eğilimi özelliklerine göre içerik etkileşimini nasıl artıracağınıza dair otomatik öneriler alın.
- **GDPR ve Çerez İzni Uyumlu:** Veritabanının şişmesini önlemek için oturum tabanlı örnekleme oranı ayarları sunar ve popüler çerez izin eklentilerine (Borlabs, Complianz, CLI ve Google Consent Mode v2) saygı duyar.

---

## Kurulum

1. `trackly` klasörünü `/wp-content/plugins/` dizinine yükleyin.
2. WordPress'teki **Eklentiler** menüsünden eklentiyi etkinleştirin.
3. Ayarları yapılandırmak için WordPress yönetim paneli yan menüsündeki **Trackly** menüsüne gidin.

---

## Yapılandırma

Trackly'yi Google Analytics 4 mülkünüze bağlamak için:

1. **GA4 Property ID (Mülk Kimliği):** Sayısal GA4 Mülk Kimliğinizi girin (GA4 > Yönetici > Mülk Ayarları bölümünden bulabilirsiniz).
2. **Service Account JSON Anahtarı:**
   - [Google Cloud Console](https://console.cloud.google.com/) adresine gidin.
   - Bir proje oluşturun (veya mevcut olanı seçin) ve **Google Analytics Data API**'yi etkinleştirin.
   - Bir **Hizmet Hesabı (Service Account)** oluşturun ve bir **JSON anahtarı** oluşturun.
   - Hizmet Hesabının oluşturulan e-posta adresini kopyalayın ve bunu Google Analytics 4 Mülk erişim yönetiminiz altında **Okuyucu (Viewer)** olarak ekleyin.
   - İndirilen JSON anahtar dosyasının içeriğini **Trackly > Ayarlar** bölümündeki metin alanına yapıştırın.
3. **Ayarları Kaydet:** Entegrasyonu tamamlamak için "Ayarları Kaydet" butonuna tıklayın.
4. **Demo Modu:** Henüz kimlik bilgileriniz yoksa, kontrol panelini ve ön yüz panellerini gerçekçi sahte verilerle test etmek için **Demo Modu** seçeneğini etkin tutun.

---

## Uluslararasılaştırma ve Çeviri

Trackly tamamen çeviriye hazırdır. Kullanıcıya yönelik tüm metinler, `trackly` text domain'i kullanılarak WordPress standart çeviri yardımcı fonksiyonları (`__()`, `_e()`, `esc_html__()` vb.) ile sarmalanmıştır.

### Kendi Dilinize Çevirme

Aşağıdaki standart yöntemleri kullanarak Trackly'yi istediğiniz dile kolayca çevirebilirsiniz:

1. **Loco Translate Eklentisi (Önerilen):**
   - WordPress sitenize **Loco Translate** eklentisini kurun ve etkinleştirin.
   - **Loco Translate > Eklentiler** bölümüne gidin ve **Trackly**'yi seçin.
   - **Yeni Dil** butonuna tıklayın, dilinizi seçin ve doğrudan tarayıcınızda çevirmeye başlayın.
2. **Poedit:**
   - WP-CLI veya Poedit gibi araçları kullanarak eklenti kod tabanından bir `.pot` şablon dosyası oluşturun.
   - Yeni bir çeviri dosyası oluşturun (`trackly-[locale].po` ve `trackly-[locale].mo`), çevirileri yapın ve bunları eklenti dizini içindeki `languages` klasörüne yerleştirin.

---

## Lisans

GPLv2 veya üzeri. Lisans detaylarını dosyaların üst kısmındaki yorum satırlarında bulabilirsiniz.
