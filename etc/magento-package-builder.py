#! /usr/bin/env python
from cStringIO import StringIO
import os
import hashlib
import datetime
from xml.dom import minidom
from xml.etree import ElementTree


class MagentoPacker(object):

    TARGET_DIRS = {
        'magelocal':        'app/code/local',
        'magecommunity':    'app/code/community',
        'magecore':         'app/code/core',
        'magedesign':       'app/design',
        'mageetc':          'app/etc',
        'magelib':          'lib',
        'magelocale':       'app/locale',
        'magemedia':        'media',
        'mageskin':         'skin',
        'magetest':         'tests',
        }

    def __init__(self, name, version, src, template, stability='stable',
                 channel='community', date=None, pretty=False, verbose=False):
        assert name, 'Please provide an extension name'
        assert version, 'Please provide a version'
        assert os.path.exists(src), '%s does not exist' % src
        assert os.path.exists(template), '%s does not exist' % template
        assert stability, 'Please provide a stability level'
        assert channel, 'Please provide a channel'
        assert type(date) is not datetime.date

        self.name = name
        self.version = version
        self.src = src
        self.template = template
        self.stability = stability
        self.channel = channel
        self.date = date if date else datetime.date.today()
        self.pretty = pretty
        self.verbose = verbose

        self.tree = ElementTree.parse(self.template)
        self.root = self.tree.getroot()

        # clean whitespace
        for elem in self.root.iter('*'):
            if elem.text is not None:
                elem.text = elem.text.strip()
            if elem.tail is not None:
                elem.tail = elem.tail.strip()

    def log(self, message):
        if self.verbose:
            sys.stdout.write(message)

    def add_node(self, name, content=None):
        elm = ElementTree.SubElement(self.root, name)
        if content:
            elm.text = content
        return elm

    def generate(self):
        self.log('Building package')

        self.add_node('name', self.name)
        self.add_node('version', self.version)
        self.add_node('stability', self.stability)
        self.add_node('channel', self.channel)
        self.add_node('date', self.date.strftime('%Y-%m-%d'))
        contents = self.add_node('contents')

        for dir_path, dir_names, file_names in os.walk(self.src):
            target, rel_path = self.get_target(dir_path)
            if not target or not file_names:
                continue

            self.log('\n')
            self.log('Found files in: %s/%s' % (target, rel_path))

            current_node = contents.find("target[@name='%s']" % target)
            if current_node is None:
                current_node = ElementTree.SubElement(contents, 'target')
                current_node.set('name', target)

            if rel_path:
                dirs = rel_path.split('/')
            else:
                dirs = ['.']

            for dir in dirs:
                dir_node = current_node.find('dir[@name=\'%s\']' % dir)
                if dir_node is None:
                    dir_node = ElementTree.SubElement(current_node, 'dir')
                    dir_node.set('name', dir)
                current_node = dir_node

            for file in file_names:
                hash = self._get_file_hash(os.path.join(dir_path, file))
                self.log('Adding file with hash %s: %s' % (hash, file))
                file_node = ElementTree.SubElement(current_node, 'file')
                file_node.set('name', file)
                file_node.set('hash', hash)

        if self.pretty:
            raw = ElementTree.tostring(self.tree.getroot(), 'utf-8')
            sys.stdout.write(minidom.parseString(raw).toprettyxml(indent="    "))
        else:
            self.tree.write(sys.stdout, 'utf-8')

        return True

    def get_target(self, dir_path):
        dir_path = dir_path[len(self.src):]
        for dir, path in self.TARGET_DIRS.items():
            if dir_path.startswith(path):
                return dir, dir_path[len(path) + 1:]
        return None, None

    def _get_file_hash(self, file):
        with open(file, 'rb') as f:
            return hashlib.md5(f.read()).hexdigest()


if __name__ == '__main__':
    import sys
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument('name', help='Extension name')
    parser.add_argument('version', help='Extension version number')
    parser.add_argument('src', help='Extension source directory')
    parser.add_argument('template', help='package.xml template')

    parser.add_argument('-s', '--stability', help='Extension stability', default='stable')
    parser.add_argument('-c', '--channel', help='Extension channel', default='community')
    parser.add_argument('-d', '--date', help='Release date', type=datetime.date)
    parser.add_argument('-p', '--pretty', help='Pretty print', action='store_true')
    parser.add_argument('-v', '--verbose', action='store_true')

    args = parser.parse_args()
    MagentoPacker(**vars(args)).generate()