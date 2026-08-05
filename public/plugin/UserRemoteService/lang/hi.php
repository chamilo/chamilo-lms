<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'उपयोगकर्ता रिमोट सेवाएँ';
$strings['plugin_comment'] = 'मेनू बार में साइट-विशिष्ट iframe-लक्षित उपयोगकर्ता-पहचानने वाले लिंक जोड़ता है।';

$strings['salt'] = 'नमक';
$strings['salt_help'] = 'गुप्त वर्ण-प्रतिशत, <em>हैश</em> URL पैरामीटर उत्पन्न करने के लिए उपयोग किया जाता है। जितना लंबा, उतना बेहतर।
<br/>रिमोट उपयोगकर्ता सेवाएँ निम्न PHP अभिव्यक्ति से उत्पन्न URL की प्रामाणिकता की जाँच कर सकती हैं:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>जहाँ
<br/><code>$salt</code> यह इनपुट मान है,
<br/><code>$userId</code> <em>username</em> URL पैरामीटर मान द्वारा संदर्भित उपयोगकर्ता का संख्या है और
<br/><code>$hash</code> <em>hash</em> URL पैरामीटर मान को समाहित करता है।';
$strings['hide_link_from_navigation_menu'] = 'मेनू से लिंक छिपाएँ';

// Please keep alphabetically sorted
$strings['CreateService'] = 'मेनू बार में सेवा जोड़ें';
$strings['DeleteServices'] = 'मेनू बार से सेवाएँ हटाएँ';
$strings['ServicesToDelete'] = 'मेनू बार से हटाने के लिए सेवाएँ';
$strings['ServiceTitle'] = 'सेवा शीर्षक';
$strings['ServiceURL'] = 'सेवा वेबसाइट स्थान (URL)';
$strings['RedirectAccessURL'] = 'उपयोगकर्ता को सेवा पर पुनर्निर्देशित करने के लिए Chamilo में उपयोग करने के लिए URL (URL)';
$strings['Actions'] = 'कार्रवाई';
$strings['AddRemoteService'] = 'रिमोट सेवा जोड़ें';
$strings['CurrentServices'] = 'वर्तमान सेवाएँ';
$strings['DeleteService'] = 'सेवा हटाएँ';
$strings['InvalidSecurityToken'] = 'अमान्य सुरक्षा टोकन।';
$strings['InvalidServiceTitle'] = 'कृपया सेवा शीर्षक दर्ज करें।';
$strings['InvalidServiceUrl'] = 'कृपया एक मान्य HTTP या HTTPS URL दर्ज करें।';
$strings['MissingSaltWarning'] = 'रिमोट सेवा लिंक उजागर करने से पहले एक सॉल्ट कॉन्फ़िगर करें। साइन किए गए उपयोगकर्ता URL उत्पन्न करने के लिए सॉल्ट आवश्यक है।';
$strings['NoServicesConfigured'] = 'अभी तक कोई रिमोट सेवा कॉन्फ़िगर नहीं की गई है।';
$strings['OpenInIframe'] = 'iframe में खोलें';
$strings['OpenRedirect'] = 'रीडायरेक्ट URL खोलें';
$strings['RemoteServicesDescription'] = 'उन बाहरी सेवाओं का प्रबंधन करें जो Chamilo से साइन किए गए उपयोगकर्ता URL प्राप्त करती हैं। केवल प्रमाणित उपयोगकर्ता ही इन लिंकों को खोल सकते हैं।';
$strings['ServiceCreated'] = 'रिमोट सेवा बनाई गई है।';
$strings['ServiceDeleted'] = 'रिमोट सेवा हटा दी गई है।';
$strings['ServiceManagement'] = 'रिमोट सेवा प्रबंधन';
$strings['ServiceUnavailable'] = 'यह रिमोट सेवा उपलब्ध नहीं है। जाँच लें कि प्लगइन सक्षम है, सॉल्ट कॉन्फ़िगर किया गया है और URL मान्य है।';
