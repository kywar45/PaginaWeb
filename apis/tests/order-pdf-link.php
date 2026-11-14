<?php
require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/OrderPdfLink.php';

function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
function rejects($callback, $status = 404) {
    try { $callback(); throw new \RuntimeException('Expected invalid PDF link'); }
    catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === $status, 'Expected rejection status'); }
}
$links = new \App\Services\OrderPdfLink(str_repeat('a', 64));
$now = 1700000000;
$token = $links->issue(91439, 23, $now);
check(preg_match('/^p1\.[A-Za-z0-9_-]{54}$/D', $token) === 1, 'URL-safe opaque format');
check($links->resolve($token, 23, $now) === 91439, 'Round trip');
check($links->issue(91439, 23, $now) !== $token, 'Fresh random nonce for every link');
check($links->resolve($token, 23, $now + 3599) === 91439, 'Valid within lifetime');
rejects(function () use ($links, $token, $now) { $links->resolve($token, 23, $now + 3600); }, 410);
rejects(function () use ($links, $token, $now) { $links->resolve($token, 99, $now); });
$otherSession = new \App\Services\OrderPdfLink(str_repeat('b', 64));
rejects(function () use ($otherSession, $token, $now) { $otherSession->resolve($token, 23, $now); });
for ($i = 0; $i < strlen($token); $i++) {
    $modified = $token;
    $modified[$i] = $modified[$i] === 'A' ? 'B' : 'A';
    rejects(function () use ($links, $modified, $now) { $links->resolve($modified, 23, $now); });
}
foreach (array(null, array($token), '91439', $token . 'A', substr($token, 0, -1), 'p1.' . str_repeat('A', 20000)) as $invalid) {
    rejects(function () use ($links, $invalid, $now) { $links->resolve($invalid, 23, $now); });
}
echo "PDF link tests passed: authenticated encryption, tampering every position, session/account binding, expiry and malformed input.\n";
