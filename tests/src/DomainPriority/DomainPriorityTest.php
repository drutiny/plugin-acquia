<?php

namespace DrutinyTests\Acquia\DomainPriority;

use Drutiny\Acquia\AcquiaSiteFactoryDomainList;
use PHPUnit\Framework\TestCase;

class DomainPriorityTest extends TestCase {

  /**
   * @covers ::prioritySort
   * @coversDefaultClass AcquiaSiteFactoryDomainList
   * @dataProvider domainDataProvider
   *
   * @param array $start
   * @param array $desired
   */
  public function testDomainPriority(array $start, array $desired) {
    $sorted = AcquiaSiteFactoryDomainList::prioritySort($start);

    // Ensure the arrays have the values in the same order.
    foreach ($sorted as $index => $domain) {
      $this->assertEquals($domain, $desired[$index]);
    }

    // Ensure the arrays are the same length.
    $this->assertEquals(count($sorted), count($desired));
  }

  /**
   * Data provider for testGetMonthRange().
   *
   * @return array
   *   Nested arrays of values to check:
   *   - $start
   *   - $desired
   */
  public function domainDataProvider() {
    return [
      // Simple use cases.
      [
        ['www.example.com', 'example.com', 'example.example.acsitefactory.com'],
        ['www.example.com', 'example.com', 'example.example.acsitefactory.com'],
      ],
      [
        ['example.com', 'www.example.com', 'example.example.acsitefactory.com'],
        ['www.example.com', 'example.com', 'example.example.acsitefactory.com'],
      ],
      [
        ['example.example.acsitefactory.com', 'example.com', 'www.example.com'],
        ['www.example.com', 'example.com', 'example.example.acsitefactory.com'],
      ],
      // Edge cases.
      [
        ['example.example.acsitefactory.com'],
        ['example.example.acsitefactory.com'],
      ],
      // .gov.au tests.
      [
        ['www.example.gov.au', 'example.gov.au', 'example-sc.example.gov.au', 'example.example.acsitefactory.com'],
        ['www.example.gov.au', 'example.gov.au', 'example-sc.example.gov.au', 'example.example.acsitefactory.com'],
      ],
      [
        ['example.gov.au', 'example-sc.example.gov.au', 'www.example.gov.au', 'example.example.acsitefactory.com'],
        ['www.example.gov.au', 'example.gov.au', 'example-sc.example.gov.au', 'example.example.acsitefactory.com'],
      ],
      [
        ['example.gov.au', 'example.example.acsitefactory.com', 'example-sc.example.gov.au', 'www.example.gov.au'],
        ['www.example.gov.au', 'example.gov.au', 'example-sc.example.gov.au', 'example.example.acsitefactory.com'],
      ],
      // Green blue.
      [
        ['www.agency.gov.au', 'example-green.agency.gov.au', 'agency.gov.au', 'agency.example.acsitefactory.com'],
        ['www.agency.gov.au', 'agency.gov.au', 'example-green.agency.gov.au', 'agency.example.acsitefactory.com'],
      ],
      // Non www.
      [
        ['agency.example.acsitefactory.com', 'example-green.agency.gov.au', 'sub.agency.gov.au'],
        ['sub.agency.gov.au', 'example-green.agency.gov.au', 'agency.example.acsitefactory.com'],
      ],
    ];
  }

}
