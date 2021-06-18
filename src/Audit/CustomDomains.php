<?php

namespace Drutiny\Acquia\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit;

/**
 * Ensure an environment has custom domains set.
 */
class CustomDomains extends Audit {

	/**
	 * @inheritdoc
	 */
	public function audit(Sandbox $sandbox) {
		$warn = FALSE;

		foreach ($this->target['acquia.cloud.environment.domains'] as $domain) {
			if ($this->urlResponds($domain) == FALSE) {
				$warn = TRUE;
			}
		}

		$this->set('warnings', $warn);

		$domains = array_filter($this->target['acquia.cloud.environment.domains'], function ($domain) {
			// Do not include ELB domains or Acquia default domains.
			return !(strpos($domain, 'acquia-sites.com') || strpos($domain, 'elb.amazonaws.com') || strpos($domain, 'acsitefactory.com'));
		});

		if (empty($domains)) {
			return FALSE;
		} elseif ($warn) {
			return self::WARNING;
		}

		$this->set('domains', array_values($domains));

		return TRUE;
	}

	/**
	 * Check if a domain responds to a ping.
	 *
	 * @param string $url
	 * @return bool
	 */
	function urlResponds( $url = NULL)
	{
		if($url == NULL) return false;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// We don't use data for anything, but it's needed for curl_getinfo.
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($httpcode == 0){
			return FALSE;
		} else {
			return TRUE;
		}
	}

}
