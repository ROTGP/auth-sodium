<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\AuthSodiumDelegate;

class CustomAuthSodiumDelegate extends AuthSodiumDelegate
{
    public function buildSignatureString($request, $user)
    {
        $toSign = [];
        $toSign['method'] = $this->getSignatureMethod($request);
        $toSign['url'] = $this->getSignatureUrl($request);
        $toSign['query_data'] = $this->getSignatureQuery($request);
        $toSign['post_data'] = $this->getSignaturePostdata($request, $toSign['method']);
        $toSign['user_identifier'] = $this->getUserIdentifier($request);
        $toSign['timestamp'] = $this->getSignatureTimestamp($request);
        $toSign['nonce'] = $this->getSignatureNonce($request, $user, true, $toSign['timestamp']);
        
        if (in_array(null, array_values($toSign), true)) {
            $this->onValidationError('unable_to_build_signature_string');
            return null;
        }
        return implode($this->glue(), array_values($toSign)) . 'foo';
    }
}
